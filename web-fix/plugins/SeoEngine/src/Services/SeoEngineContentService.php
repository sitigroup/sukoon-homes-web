<?php

namespace App\Plugins\SeoEngine\Services;

use App\Plugins\SeoEngine\Models\SeoEngineContentFailure;
use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Services\Content\SeoEngineAiProviderInterface;
use App\Plugins\SeoEngine\Services\Content\SeoEngineClaudeContentProvider;
use App\Plugins\SeoEngine\Services\Content\SeoEngineGeminiContentProvider;
use Illuminate\Support\Facades\Log;

class SeoEngineContentService
{
    private const DRIFT_THRESHOLD = 0.15;

    public function __construct(
        private SeoEngineSettingsService $settings,
        private SeoEnginePageDataService $pageData,
        private SeoEnginePageGeneratorService $generator,
        private SeoEngineGeminiContentProvider $gemini,
        private SeoEngineClaudeContentProvider $claude
    ) {
    }

    /**
     * @param  list<string>|null  $paths  Limit to these paths; null = all eligible pages
     * @return array{generated:int,skipped:int,failed:int,fallback:int,paths:list<string>}
     */
    public function generate(?array $paths = null, bool $force = false, ?int $limit = null): array
    {
        $query = SeoEnginePage::query()->where('lock_content', false)->orderBy('path');
        if ($paths !== null) {
            $query->whereIn('path', $paths);
        } else {
            $query->where('listing_count', '>=', $this->minListingsForAi());
            $query->where(function ($q) {
                $q->whereNull('intro_html')
                    ->orWhere('intro_html', '')
                    ->orWhereNull('content_generated_at');
            });
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        $pages = $query->get();
        $stats = ['generated' => 0, 'skipped' => 0, 'failed' => 0, 'fallback' => 0, 'paths' => [], 'tokens' => ['prompt' => 0, 'completion' => 0, 'total' => 0]];
        $provider = $this->provider();
        $delayMs = max(0, (int) $this->settings->get('ai_rate_limit_ms', 2000));

        foreach ($pages as $page) {
            if (! $this->eligibleForAiContent($page)) {
                if ($force || $paths !== null) {
                    $this->applyTemplateFallback($page);
                    $stats['fallback']++;
                } else {
                    $stats['skipped']++;
                }

                continue;
            }

            if (! $force && ! $this->needsGeneration($page)) {
                $stats['skipped']++;

                continue;
            }

            $ok = $this->generateForPage($page, $provider, $stats);
            if ($ok) {
                $stats['generated']++;
                $stats['paths'][] = $page->path;
            } else {
                $stats['failed']++;
            }

            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        $this->settings->set('cron_last_generate_content_at', now()->toIso8601String(), 'cron');

        return $stats;
    }

    public function generateForPage(SeoEnginePage $page, ?SeoEngineAiProviderInterface $provider = null, ?array &$stats = null): bool
    {
        if ($page->lock_content) {
            return false;
        }

        if (! $this->eligibleForAiContent($page)) {
            $this->applyTemplateFallback($page);

            return false;
        }

        $provider ??= $this->provider();
        $prompt = $this->buildPrompt($page);
        $attempts = 3;
        $lastError = 'unknown';

        for ($i = 0; $i < $attempts; $i++) {
            if ($i > 0) {
                usleep((int) (500000 * (2 ** ($i - 1))));
            }

            $options = [];
            if ($provider->name() === 'claude') {
                $options['api_key'] = $this->claudeApiKey();
                $options['model'] = (string) $this->settings->get('ai_model_claude', 'claude-3-5-haiku-20241022');
                $options['temperature'] = 0.3;
            }

            $result = $provider->generate($prompt, $options);
            if ($result['success'] ?? false) {
                if ($stats !== null && isset($result['usage']) && is_array($result['usage'])) {
                    $stats['tokens']['prompt'] += (int) ($result['usage']['prompt_tokens'] ?? 0);
                    $stats['tokens']['completion'] += (int) ($result['usage']['completion_tokens'] ?? 0);
                    $stats['tokens']['total'] += (int) ($result['usage']['total_tokens'] ?? 0);
                }
                $parsed = $this->parseResponse((string) ($result['text'] ?? ''));
                if ($parsed === null) {
                    $lastError = 'Invalid JSON structure from provider';

                    continue;
                }

                $page->update([
                    'intro_html' => $parsed['intro_html'],
                    'faq_json' => $parsed['faq_json'],
                    'content_generated_at' => now(),
                    'content_review_status' => 'pending',
                ]);
                $this->pageData->clearPageCache($page->path);

                return true;
            }

            $lastError = (string) ($result['error'] ?? 'Provider error');
        }

        SeoEngineContentFailure::query()->create([
            'path' => $page->path,
            'provider' => $provider->name(),
            'error' => $lastError,
        ]);
        Log::warning('SeoEngine content generation failed', ['path' => $page->path, 'error' => $lastError]);

        return false;
    }

    private function provider(): SeoEngineAiProviderInterface
    {
        $name = (string) $this->settings->get('ai_provider', 'gemini');

        return $name === 'claude' ? $this->claude : $this->gemini;
    }

    private function claudeApiKey(): string
    {
        $fromSettings = (string) $this->settings->get('ai_api_key', '');

        return $fromSettings !== '' ? $fromSettings : (string) env('SEO_AI_API_KEY', '');
    }

    private function minListingsForAi(): int
    {
        return max(0, (int) $this->settings->get('ai_content_min_listings', 1));
    }

    private function eligibleForAiContent(SeoEnginePage $page): bool
    {
        return (int) $page->listing_count >= $this->minListingsForAi();
    }

    public function applyTemplateFallback(SeoEnginePage $page): void
    {
        if ($page->lock_content) {
            return;
        }

        $page->update([
            'intro_html' => null,
            'faq_json' => null,
            'content_generated_at' => null,
            'content_review_status' => null,
        ]);
        $this->pageData->clearPageCache($page->path);
    }

    private function needsGeneration(SeoEnginePage $page): bool
    {
        if (empty($page->intro_html) || $page->content_generated_at === null) {
            return true;
        }

        $filters = is_array($page->params) ? $page->params : [];
        $current = $this->generator->countListings($filters)['count'];
        $stored = (int) $page->listing_count;
        if ($stored <= 0) {
            return $current > 0;
        }

        $drift = abs($current - $stored) / $stored;

        return $drift > self::DRIFT_THRESHOLD;
    }

    private function buildPrompt(SeoEnginePage $page): string
    {
        $template = (string) $this->settings->get('prompt_template_content', '');
        if (trim($template) === '') {
            $template = $this->defaultPromptTemplate();
        }
        $payload = $this->pageData->getByPath($page->path) ?? [];
        $facts = $this->buildFacts($page, $payload);

        return str_replace(
            ['{facts_json}', '{page_title}'],
            [
                json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                (string) $page->title,
            ],
            $template
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function buildFacts(SeoEnginePage $page, array $payload): array
    {
        $filters = is_array($page->params) ? $page->params : [];
        $locality = $payload['locality_stats'] ?? [];
        $current = is_array($locality['current'] ?? null) ? $locality['current'] : null;
        $areaName = $this->extractAreaName($payload, $page);
        $comboLabel = $this->extractComboLabel($page);
        $isCombo = str_starts_with((string) $page->page_type, 'rent_combo');
        $pageListings = (int) $page->listing_count;

        $rentStats = null;
        if ($current) {
            $periodLabel = $this->formatStatsPeriod((string) ($current['period'] ?? ''));
            $rentStats = [
                'scope' => 'area',
                'area_name' => $areaName,
                'period' => $current['period'] ?? null,
                'period_label' => $periodLabel,
                'avg_rent_inr' => $current['avg_rent'] ?? null,
                'min_rent_inr' => $current['min_rent'] ?? null,
                'max_rent_inr' => $current['max_rent'] ?? null,
                'attribution' => $isCombo && $pageListings === 0
                    ? "Quote rent ONLY for {$areaName} (e.g. \"As of {$periodLabel}, average rent in {$areaName}…\"). NEVER attribute rent to {$comboLabel}."
                    : "Quote rent for {$areaName} or the page topic; never invent type-specific rent without page listings.",
            ];
        }

        return [
            'path' => $page->path,
            'page_type' => $page->page_type,
            'title' => $page->title,
            'h1' => $page->h1,
            'area_name' => $areaName,
            'combo_facet_label' => $comboLabel,
            'is_combo_page' => $isCombo,
            'page_has_matching_listings' => $pageListings > 0,
            'is_indexable' => $page->is_indexable,
            'rent_stats' => $rentStats,
            'nearby_places' => array_slice($payload['nearby_places'] ?? [], 0, 6),
            'breadcrumbs' => $payload['breadcrumbs'] ?? [],
            'platform_usps' => [
                'verified listings',
                'online rent agreement',
                'tenant KYC',
            ],
            'writing_rules' => [
                'Do not state exact listing counts in intro_html or faq_json (UI shows live counts).',
                'Subjective locality adjectives forbidden unless present in facts (no sought-after, developing, prime, attractive, promising, exclusive).',
                'Use Indian English with -ise spelling (e.g. specialise, organise).',
                'Always use ₹ for rupee amounts; never write INR.',
                'Vary the opening sentence structure — do not repeat "Discover rental homes in…" patterns.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractAreaName(array $payload, SeoEnginePage $page): string
    {
        $segments = array_values(array_filter(explode('/', trim($page->path, '/'))));
        if (($segments[0] ?? '') !== 'rent') {
            return 'this area';
        }

        $crumbs = $payload['breadcrumbs'] ?? [];
        $crumbLabel = function (string $targetPath) use ($crumbs): ?string {
            if (! is_array($crumbs)) {
                return null;
            }
            $targetPath = rtrim($targetPath, '/');
            foreach ($crumbs as $crumb) {
                if (! is_array($crumb)) {
                    continue;
                }
                if (rtrim((string) ($crumb['path'] ?? ''), '/') === $targetPath && ! empty($crumb['label'])) {
                    return (string) $crumb['label'];
                }
            }

            return null;
        };

        // Combo facet: /rent/{city}/{area}/{facet}/ — stats are for {area}, not the facet.
        if (str_starts_with((string) $page->page_type, 'rent_combo') && count($segments) >= 4) {
            $areaPath = '/rent/' . $segments[1] . '/' . $segments[2];

            return $crumbLabel($areaPath) ?? ucwords(str_replace('-', ' ', $segments[2]));
        }

        // Area or subarea location page.
        if (count($segments) >= 3) {
            $locParts = array_slice($segments, 1);
            $locPath = '/rent/' . implode('/', $locParts);

            return $crumbLabel($locPath) ?? ucwords(str_replace('-', ' ', $locParts[count($locParts) - 1]));
        }

        if (count($segments) >= 2) {
            $cityPath = '/rent/' . $segments[1];

            return $crumbLabel($cityPath) ?? ucwords(str_replace('-', ' ', $segments[1]));
        }

        return 'this area';
    }

    private function extractComboLabel(SeoEnginePage $page): ?string
    {
        if (! str_starts_with((string) $page->page_type, 'rent_combo')) {
            return null;
        }

        $trimmed = rtrim($page->path, '/');
        $segment = basename($trimmed);

        return strtoupper($segment) === $segment && preg_match('/^\d/', $segment)
            ? strtoupper($segment)
            : ucwords(str_replace('-', ' ', $segment));
    }

    private function formatStatsPeriod(string $period): string
    {
        if (preg_match('/^(\d{4})-(\d{2})$/', $period, $m)) {
            $months = ['', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            $month = $months[(int) $m[2]] ?? $m[2];

            return "{$month} {$m[1]}";
        }

        return $period;
    }

    private function defaultPromptTemplate(): string
    {
        return <<<'PROMPT'
You write SEO intro copy and FAQs for Sukoon Homes rental landing pages.

FACTS (use ONLY these — do not invent prices, landmarks, policies, or locality characterisations not listed):
{facts_json}

OUTPUT: Return ONLY valid JSON (no markdown fences) with this shape:
{
  "intro_html": "<p>40-60 word direct answer first sentence.</p><p>100-150 additional words.</p>",
  "faq_json": [
    {"question": "...", "answer": "..."}
  ]
}

Hard rules:
1. VERIFIABLE ONLY — Do not use subjective locality claims (sought-after, developing, prime, attractive, promising, exclusive, hassle-free neighbourhood, etc.) unless explicitly provided in facts.
2. NO LISTING COUNTS in intro_html or faq_json prose — the page UI shows live counts. Never write "1 listing", "2 properties", "currently have N homes", etc.
3. RENT STATS — When page_has_matching_listings is false on a combo page, cite rent_stats ONLY for the area_name (e.g. "As of June 2026, average rent in Kailash Puri…"). Never say "average rent for a 1BHK" when page_has_matching_listings is false.
4. DATED RENT — When using rent_stats, include the period_label (e.g. "As of June 2026").
5. INDIAN ENGLISH — Use -ise spelling consistently (specialise, organise, prioritise). Always use the ₹ symbol for rupee amounts; never write "INR".
6. OPENING VARIETY — The first sentence of intro_html MUST use a different structure from other pages. Rotate among these patterns (pick one not used on sibling pages):
   a) Lead with the location + what renters can do: "Renters in {area} can browse verified flats and houses on Sukoon Homes."
   b) Lead with a dated rent fact: "As of {period}, typical rents in {area} start around ₹{min}."
   c) Lead with the search intent: "Looking for a rental home in {area}, Barmer? Sukoon Homes lists verified properties you can compare online."
   d) Lead with the platform action: "Sukoon Homes helps you find and secure a rental in {area} with verified listings and online agreements."
   e) Lead with a practical question answered: "Need a rental in {area}? Sukoon Homes offers verified listings, tenant KYC, and online rent agreements."
   Do not start multiple pages with "Discover rental homes in…".
7. USP VARIETY — Mention each platform USP at most once; do not copy the same USP sentence pattern used on other pages.
8. intro_html: first paragraph direct answer (40-60 words), second paragraph context (100-150 words total).
9. faq_json: 4-6 items, grounded in facts only; use ₹ for all rupee amounts.

Page title context: {page_title}
PROMPT;
    }

    /**
     * @return array{intro_html:string,faq_json:list<array{question:string,answer:string}>}|null
     */
    private function parseResponse(string $raw): ?array
    {
        $raw = trim($raw);
        $raw = preg_replace('/^```json\s*|\s*```$/s', '', $raw) ?? $raw;
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            if (preg_match('/\{[\s\S]*\}/', $raw, $m)) {
                $data = json_decode($m[0], true);
            }
        }
        if (! is_array($data) || empty($data['intro_html'])) {
            return null;
        }

        $faqs = [];
        foreach ($data['faq_json'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }
            $q = trim((string) ($item['question'] ?? $item['q'] ?? ''));
            $a = trim((string) ($item['answer'] ?? $item['a'] ?? ''));
            if ($q !== '' && $a !== '') {
                $faqs[] = ['question' => $q, 'answer' => $a];
            }
        }

        if (count($faqs) < 4) {
            return null;
        }

        return [
            'intro_html' => (string) $data['intro_html'],
            'faq_json' => array_slice($faqs, 0, 6),
        ];
    }
}
