<?php

namespace App\Plugins\Theme\Services;

use App\Plugins\Theme\Models\ThemeSetting;
use App\Plugins\Theme\Models\ThemeVersion;
use App\Plugins\Theme\Support\ThemePresets;
use App\Plugins\Theme\Support\ThemeTokens;
use App\Plugins\Theme\Support\ThemeContrast;
use Illuminate\Support\Facades\Cache;

class ThemeService
{
    private const CACHE_KEY = 'sukoon_theme_published_v1';

    public static function setting(): ThemeSetting
    {
        return ThemeSetting::query()->firstOrCreate([], [
            'draft_payload' => ThemePresets::defaultPreset(),
            'published_payload' => null,
            'active_preset' => ThemePresets::defaultPreset()['slug'],
        ]);
    }

    public static function isPublished(): bool
    {
        $payload = self::setting()->published_payload;

        return is_array($payload) && ! empty($payload);
    }

    public static function publishedPayload(): ?array
    {
        if (! self::isPublished()) {
            return null;
        }

        $ttl = (int) config('sukoon-theme.cache_ttl_seconds', 300);

        return Cache::remember(self::CACHE_KEY, $ttl, function () {
            $setting = self::setting();
            $payload = $setting->published_payload ?? ThemePresets::defaultPreset();
            $normalized = ThemeTokens::normalize($payload);
            $normalized['css_variables'] = ThemeTokens::cssVariables($normalized);

            return $normalized;
        });
    }

    public static function draftPayload(): array
    {
        $setting = self::setting();
        $payload = $setting->draft_payload ?? $setting->published_payload ?? ThemePresets::defaultPreset();

        return ThemeTokens::normalize($payload);
    }

    public static function adminState(): array
    {
        $setting = self::setting();

        $published = self::publishedPayload();
        $draft = self::draftPayload();

        return [
            'draft' => $draft,
            'published' => $published,
            'draft_semantic' => ThemeContrast::deriveSemantic($draft['tokens']),
            'is_published' => $published !== null,
            'active_preset' => $setting->active_preset,
            'presets' => array_values(ThemePresets::all()),
            'versions' => ThemeVersion::query()
                ->orderByDesc('version_number')
                ->limit(20)
                ->get(['id', 'version_number', 'preset_slug', 'action', 'note', 'created_at']),
            'has_unpublished_draft' => $published === null
                || json_encode(self::draftPayload()) !== json_encode($published),
        ];
    }

    public static function saveDraft(array $input, ?int $userId = null): array
    {
        $setting = self::setting();
        $normalized = ThemeTokens::normalize($input);

        if (! empty($input['preset_slug'])) {
            $setting->active_preset = (string) $input['preset_slug'];
        }

        $setting->draft_payload = $normalized;
        $setting->save();

        self::recordVersion($normalized, 'draft_saved', $userId, 'Draft saved');

        return self::adminState();
    }

    public static function publish(?int $userId = null): array
    {
        $setting = self::setting();
        $draft = self::draftPayload();

        $setting->published_payload = $draft;
        $setting->published_at = now();
        $setting->published_by = $userId;
        $setting->save();

        self::clearCache();
        self::recordVersion($draft, 'published', $userId, 'Theme published');

        return self::adminState();
    }

    /** Unpublish live theme — frontend uses WRTeam defaults until publish again. */
    public static function unpublish(?int $userId = null): array
    {
        $setting = self::setting();

        $setting->published_payload = null;
        $setting->published_at = null;
        $setting->published_by = null;
        $setting->save();

        self::clearCache();
        self::recordVersion(self::draftPayload(), 'unpublished', $userId, 'Theme unpublished — live site restored to default');

        return self::adminState();
    }

    public static function resetToDefault(?int $userId = null): array
    {
        $default = ThemePresets::defaultPreset();
        $normalized = ThemeTokens::normalize($default);

        $setting = self::setting();
        $setting->draft_payload = $normalized;
        $setting->published_payload = $normalized;
        $setting->active_preset = $default['slug'];
        $setting->published_at = now();
        $setting->published_by = $userId;
        $setting->save();

        self::clearCache();
        self::recordVersion($normalized, 'reset', $userId, 'Reset to Sukoon Pure Black Luxury default');

        return self::adminState();
    }

    public static function applyPreset(string $slug, ?int $userId = null): array
    {
        $preset = ThemePresets::get($slug);
        if (! $preset) {
            throw new \InvalidArgumentException('Unknown preset: ' . $slug);
        }

        $normalized = ThemeTokens::normalize($preset);
        $setting = self::setting();
        $setting->draft_payload = $normalized;
        $setting->active_preset = $slug;
        $setting->save();

        self::recordVersion($normalized, 'preset_applied', $userId, 'Applied preset: ' . $preset['name']);

        return self::adminState();
    }

    public static function restoreVersion(int $versionId, ?int $userId = null): array
    {
        $version = ThemeVersion::query()->findOrFail($versionId);
        $normalized = ThemeTokens::normalize($version->payload);

        $setting = self::setting();
        $setting->draft_payload = $normalized;
        if ($version->preset_slug) {
            $setting->active_preset = $version->preset_slug;
        }
        $setting->save();

        self::recordVersion($normalized, 'restored', $userId, 'Restored version #' . $version->version_number);

        return self::adminState();
    }

    public static function publicApiResponse(): array
    {
        if (! self::isPublished()) {
            return [
                'error' => false,
                'message' => 'Theme not published yet',
                'data' => [
                    'published' => false,
                    'tokens' => null,
                    'typography' => null,
                    'border_radius' => null,
                    'shadow_intensity' => null,
                    'shadow' => null,
                    'css_variables' => null,
                    'preset_slug' => null,
                    'version' => ThemeVersion::query()->max('version_number') ?? 0,
                ],
            ];
        }

        $published = self::publishedPayload();

        return [
            'error' => false,
            'message' => 'Theme settings',
            'data' => [
                'published' => true,
                'tokens' => $published['tokens'],
                'typography' => $published['typography'],
                'border_radius' => $published['border_radius'],
                'shadow_intensity' => $published['shadow_intensity'],
                'shadow' => $published['shadow'],
                'css_variables' => ThemeTokens::cssVariables($published),
                'preset_slug' => $published['preset_slug'] ?? null,
                'version' => ThemeVersion::query()->max('version_number') ?? 1,
            ],
        ];
    }

    private static function recordVersion(array $payload, string $action, ?int $userId, ?string $note): void
    {
        $next = ((int) ThemeVersion::query()->max('version_number')) + 1;

        ThemeVersion::query()->create([
            'version_number' => $next,
            'payload' => $payload,
            'preset_slug' => $payload['preset_slug'] ?? null,
            'action' => $action,
            'note' => $note,
            'created_by' => $userId,
        ]);
    }

    private static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
