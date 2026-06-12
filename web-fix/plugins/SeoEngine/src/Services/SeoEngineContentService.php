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
     * @return array{generated:int,skipped:int,failed:int,paths:list<string>}
     */
    public function generate(?array $paths = null, bool $force = false, ?int $limit = null): array
    {
        $query = SeoEnginePage::query()->where('lock_content', false)->orderBy('path');
        if ($paths !== null) {
            $query->whereIn('path', $paths);
        } else {
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
        $stats = ['generated' => 0, 'skipped' => 0, 'failed' => 0, 'paths' => []];
        $provider = $this->provider();
        $delayMs = max(0, (int) $this->settings->get('ai_rate_limit_ms', 2000));

        foreach ($pages as $page) {
            if (! $force && ! $this->needsGeneration($page)) {
                $stats['skipped']++;

                continue;
            }

            $ok = $this->generateForPage($page, $provider);
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

    public function generateForPage(SeoEnginePage $page, ?SeoEngineAiProviderInterface $provider = null): bool
    {
        if ($page->lock_content) {
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
        $facts = [
            'path' => $page->path,
            'page_type' => $page->page_type,
            'title' => $page->title,
            'h1' => $page->h1,
            'listing_count' => $page->listing_count,
            'is_indexable' => $page->is_indexable,
            'locality_stats' => $payload['locality_stats'] ?? null,
            'nearby_places' => array_slice($payload['nearby_places'] ?? [], 0, 6),
            'breadcrumbs' => $payload['breadcrumbs'] ?? [],
            'site_usps' => $this->settings->get('knows_about', []),
            'platform_features' => [
                'verified listings',
                'online rent agreement',
                'tenant KYC',
            ],
        ];

        return str_replace(
            ['{facts_json}', '{page_title}', '{listing_count}'],
            [
                json_encode($facts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                (string) $page->title,
                (string) $page->listing_count,
            ],
            $template
        );
    }

    private function defaultPromptTemplate(): string
    {
        return <<<'PROMPT'
You write SEO intro copy and FAQs for Sukoon Homes rental landing pages in Indian English.

FACTS (use ONLY these — do not invent prices, counts, landmarks, or policies not listed):
{facts_json}

OUTPUT: Return ONLY valid JSON (no markdown fences) with this shape:
{
  "intro_html": "<p>40-60 word direct answer first sentence.</p><p>100-150 additional words.</p>",
  "faq_json": [
    {"question": "...", "answer": "..."}
  ]
}

Rules:
- intro_html: first paragraph is a direct answer (40-60 words), second paragraph adds context (100-150 words total body).
- faq_json: 4-6 items, answers grounded in facts only.
- Mention verified listings / online rent agreement / tenant KYC only as platform USPs (already in facts).
- Page title context: {page_title} ({listing_count} listings).
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
