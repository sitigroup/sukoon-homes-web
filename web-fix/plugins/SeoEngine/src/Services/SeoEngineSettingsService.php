<?php

namespace App\Plugins\SeoEngine\Services;

use App\Plugins\SeoEngine\Models\SeoEngineSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SeoEngineSettingsService
{
    public const CACHE_KEY = 'seo_engine:settings:all';

    public const CACHE_TTL_SECONDS = 600;

    /** @var list<string> Keys never exposed on the public API. */
    private const SECRET_KEYS = [
        'ai_api_key',
        'indexnow_key',
        'prompt_templates',
        'internal_notes',
    ];

    public function defaults(): array
    {
        return [
            'site_name' => 'Sukoon Homes',
            'site_url' => 'https://homes.sukoon.group',
            'logo_url' => 'https://homes.sukoon.group/logo.png',
            'site_description' => 'Verified rental homes in Rajasthan',
            'same_as' => [
                'https://www.facebook.com/sukoongroup',
                'https://www.instagram.com/sukoongroup',
            ],
            'knows_about' => [
                'rental homes',
                'tenant verification',
                'online rent agreement',
            ],
            'index_threshold' => 3,
            'budget_bands' => [
                ['label' => 'Under ₹10,000', 'min' => null, 'max' => 9999],
                ['label' => '₹10,000 – ₹15,000', 'min' => 10000, 'max' => 14999],
                ['label' => '₹15,000 – ₹25,000', 'min' => 15000, 'max' => 24999],
                ['label' => '₹25,000+', 'min' => 25000, 'max' => null],
            ],
            'schema_toggles' => [
                'Organization' => true,
                'RealEstateAgent' => true,
                'RealEstateListing' => true,
                'BreadcrumbList' => true,
                'FAQPage' => true,
                'ItemList' => true,
            ],
            'robots_txt' => "User-agent: *\nAllow: /\n\nSitemap: https://homes.sukoon.group/sitemap.xml\n",
            'llms_txt' => "# Sukoon Homes\n\n> Verified rental homes in Rajasthan.\n",
            'ai_bot_policy' => [
                'Googlebot' => 'allow',
                'Bingbot' => 'allow',
                'GPTBot' => 'block',
                'OAI-SearchBot' => 'block',
                'ChatGPT-User' => 'block',
                'ClaudeBot' => 'block',
                'Claude-SearchBot' => 'block',
                'PerplexityBot' => 'block',
                'Google-Extended' => 'block',
                'CCBot' => 'block',
                'Meta-ExternalAgent' => 'block',
            ],
            'cron_last_generate_pages_at' => null,
            'cron_last_build_sitemaps_at' => null,
            'cron_last_generate_content_at' => null,
        ];
    }

    public function all(): array
    {
        if (! Schema::hasTable('seo_engine_settings')) {
            return $this->defaults();
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            $stored = SeoEngineSetting::query()->pluck('value', 'key')->all();

            if (empty($stored)) {
                $this->seedDefaults();
                $stored = SeoEngineSetting::query()->pluck('value', 'key')->all();
            }

            return array_replace($this->defaults(), $stored);
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public function set(string $key, mixed $value, string $group = 'general'): void
    {
        SeoEngineSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
        $this->clearCache();
    }

    /**
     * @param  array<string, mixed>  $pairs
     */
    public function setMany(array $pairs, string $group = 'general'): void
    {
        foreach ($pairs as $key => $value) {
            SeoEngineSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group]
            );
        }
        $this->clearCache();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('seo_engine:api:public_settings');
    }

    public function publicSubset(): array
    {
        $all = $this->all();

        return [
            'site_name' => $all['site_name'] ?? null,
            'site_url' => $all['site_url'] ?? null,
            'logo_url' => $all['logo_url'] ?? null,
            'site_description' => $all['site_description'] ?? null,
            'same_as' => $all['same_as'] ?? [],
            'knows_about' => $all['knows_about'] ?? [],
            'index_threshold' => (int) ($all['index_threshold'] ?? 3),
            'budget_bands' => $all['budget_bands'] ?? [],
            'schema_toggles' => $all['schema_toggles'] ?? [],
            'ai_bot_policy' => $all['ai_bot_policy'] ?? [],
        ];
    }

    public function isSecretKey(string $key): bool
    {
        return in_array($key, self::SECRET_KEYS, true);
    }

    private function seedDefaults(): void
    {
        foreach ($this->defaults() as $key => $value) {
            SeoEngineSetting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $this->groupForKey($key)]
            );
        }
    }

    private function groupForKey(string $key): string
    {
        return match ($key) {
            'robots_txt', 'llms_txt', 'ai_bot_policy' => 'bots',
            'schema_toggles' => 'schema',
            'budget_bands', 'index_threshold' => 'indexing',
            'cron_last_generate_pages_at', 'cron_last_build_sitemaps_at', 'cron_last_generate_content_at' => 'cron',
            default => 'identity',
        };
    }
}
