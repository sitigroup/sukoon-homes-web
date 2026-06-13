<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Services\SeoEngineSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SeoEngineSettingsController extends Controller
{
    private const AI_BOTS = [
        'Googlebot',
        'Bingbot',
        'GPTBot',
        'OAI-SearchBot',
        'ChatGPT-User',
        'ClaudeBot',
        'Claude-SearchBot',
        'PerplexityBot',
        'Google-Extended',
        'CCBot',
        'Meta-ExternalAgent',
    ];

    private const SCHEMA_TYPES = [
        'Organization',
        'RealEstateAgent',
        'RealEstateListing',
        'BreadcrumbList',
        'FAQPage',
        'ItemList',
    ];

    private function denyUnlessSettings(): void
    {
        if (! function_exists('has_permissions') || ! has_permissions('settings', 'seo_engine')) {
            abort(403);
        }
    }

    public function index(SeoEngineSettingsService $settings): View
    {
        $this->denyUnlessSettings();

        return view('seo-engine::admin.seo-engine.settings', [
            'settings' => $settings->all(),
            'aiBots' => self::AI_BOTS,
            'schemaTypes' => self::SCHEMA_TYPES,
        ]);
    }

    public function store(Request $request, SeoEngineSettingsService $settings): RedirectResponse
    {
        $this->denyUnlessSettings();

        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'site_url' => ['required', 'url', 'max:512'],
            'logo_url' => ['nullable', 'url', 'max:512'],
            'site_description' => ['nullable', 'string', 'max:2000'],
            'same_as' => ['nullable', 'array'],
            'same_as.*' => ['nullable', 'url', 'max:512'],
            'knows_about' => ['nullable', 'array'],
            'knows_about.*' => ['nullable', 'string', 'max:120'],
            'index_threshold' => ['required', 'integer', 'min:1', 'max:100'],
            'budget_bands' => ['nullable', 'array'],
            'budget_bands.*.label' => ['nullable', 'string', 'max:120'],
            'budget_bands.*.min' => ['nullable', 'integer', 'min:0'],
            'budget_bands.*.max' => ['nullable', 'integer', 'min:0'],
            'type_facets' => ['nullable', 'array'],
            'type_facets.*' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9-]+$/'],
            'schema_toggles' => ['nullable', 'array'],
            'robots_txt' => ['nullable', 'string', 'max:20000'],
            'llms_txt' => ['nullable', 'string', 'max:20000'],
            'ai_bot_policy' => ['nullable', 'array'],
            'ai_bot_policy.*' => ['nullable', 'in:allow,block'],
            'ai_provider' => ['nullable', 'in:gemini,claude'],
            'ai_api_key' => ['nullable', 'string', 'max:512'],
            'ai_model_claude' => ['nullable', 'string', 'max:120'],
            'ai_rate_limit_ms' => ['nullable', 'integer', 'min:0', 'max:60000'],
            'ai_content_min_listings' => ['nullable', 'integer', 'min:0', 'max:100'],
            'prompt_template_content' => ['nullable', 'string', 'max:20000'],
            'lead_notify_phone' => ['nullable', 'string', 'max:32'],
            'area_hero_images_json' => ['nullable', 'string', 'max:50000'],
            'default_area_hero_url' => ['nullable', 'url', 'max:512'],
            'gsc_property' => ['nullable', 'string', 'max:512'],
            'gsc_client_id' => ['nullable', 'string', 'max:512'],
            'gsc_client_secret' => ['nullable', 'string', 'max:512'],
            'ga4_measurement_id' => ['nullable', 'string', 'max:32', 'regex:/^(G-[A-Z0-9]+)?$/'],
            'ga4_enabled' => ['nullable', 'boolean'],
        ]);

        $sameAs = array_values(array_filter($validated['same_as'] ?? []));
        $knowsAbout = array_values(array_filter($validated['knows_about'] ?? []));

        $schemaToggles = [];
        foreach (self::SCHEMA_TYPES as $type) {
            $schemaToggles[$type] = $request->boolean('schema_toggles.' . $type);
        }

        $aiPolicy = [];
        foreach (self::AI_BOTS as $bot) {
            $aiPolicy[$bot] = $validated['ai_bot_policy'][$bot] ?? 'block';
        }

        $bands = [];
        foreach ($validated['budget_bands'] ?? [] as $band) {
            if (empty($band['label'])) {
                continue;
            }
            $bands[] = [
                'label' => $band['label'],
                'min' => isset($band['min']) && $band['min'] !== '' ? (int) $band['min'] : null,
                'max' => isset($band['max']) && $band['max'] !== '' ? (int) $band['max'] : null,
            ];
        }

        $typeFacets = array_values(array_filter(array_map(
            fn ($f) => Str::slug((string) $f),
            $validated['type_facets'] ?? []
        )));

        $settings->setMany([
            'site_name' => $validated['site_name'],
            'site_url' => rtrim($validated['site_url'], '/'),
            'logo_url' => $validated['logo_url'] ?? null,
            'site_description' => $validated['site_description'] ?? null,
            'same_as' => $sameAs,
            'knows_about' => $knowsAbout,
            'index_threshold' => (int) $validated['index_threshold'],
            'budget_bands' => $bands ?: $settings->get('budget_bands'),
            'type_facets' => $typeFacets ?: $settings->get('type_facets'),
            'schema_toggles' => $schemaToggles,
            'robots_txt' => $validated['robots_txt'] ?? '',
            'llms_txt' => $validated['llms_txt'] ?? '',
            'ai_bot_policy' => $aiPolicy,
            'ai_provider' => $validated['ai_provider'] ?? $settings->get('ai_provider', 'gemini'),
            'ai_model_claude' => $validated['ai_model_claude'] ?? $settings->get('ai_model_claude'),
            'ai_rate_limit_ms' => (int) ($validated['ai_rate_limit_ms'] ?? $settings->get('ai_rate_limit_ms', 2000)),
            'ai_content_min_listings' => (int) ($validated['ai_content_min_listings'] ?? $settings->get('ai_content_min_listings', 1)),
            'prompt_template_content' => $validated['prompt_template_content'] ?? '',
            'lead_notify_phone' => $validated['lead_notify_phone'] ?? '',
            'default_area_hero_url' => $validated['default_area_hero_url'] ?? '',
            'gsc_property' => $validated['gsc_property'] ?? '',
            'gsc_client_id' => $validated['gsc_client_id'] ?? '',
            'ga4_measurement_id' => $validated['ga4_measurement_id'] ?? '',
            'ga4_enabled' => $request->boolean('ga4_enabled'),
        ]);

        $heroJson = trim((string) ($validated['area_hero_images_json'] ?? ''));
        if ($heroJson !== '') {
            $decoded = json_decode($heroJson, true);
            if (is_array($decoded)) {
                $settings->set('area_hero_images', $decoded, 'media');
            }
        }

        if (! empty($validated['gsc_client_secret'])) {
            $settings->set('gsc_client_secret', $validated['gsc_client_secret'], 'secrets');
        }

        if (! empty($validated['ai_api_key'])) {
            $settings->set('ai_api_key', $validated['ai_api_key'], 'secrets');
        }

        return back()->with('success', __('seo-engine::seo_engine.settings_saved'));
    }

    public function clearCache(SeoEngineSettingsService $settings): RedirectResponse
    {
        $this->denyUnlessSettings();
        $settings->clearCache();

        return back()->with('success', __('seo-engine::seo_engine.cache_cleared'));
    }
}
