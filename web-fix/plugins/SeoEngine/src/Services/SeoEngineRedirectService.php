<?php

namespace App\Plugins\SeoEngine\Services;

use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Models\SeoEngineRedirect;
use App\Plugins\SeoEngine\Models\SeoEngineSlugHistory;
use Illuminate\Support\Facades\Cache;

class SeoEngineRedirectService
{
    public const CACHE_PREFIX = 'seo_engine:redirect:';

    public const CACHE_TTL_SECONDS = 600;

    public function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path .= '/';
        }

        return $path;
    }

    public function recordSlugChange(
        string $entityType,
        int $entityId,
        ?string $oldSlug,
        ?string $newSlug,
        callable $pathForSlug
    ): void {
        $oldSlug = trim((string) $oldSlug);
        $newSlug = trim((string) $newSlug);

        if ($oldSlug === '' || $newSlug === '' || $oldSlug === $newSlug) {
            return;
        }

        SeoEngineSlugHistory::query()->create([
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_slug' => $oldSlug,
            'new_slug' => $newSlug,
            'changed_at' => now(),
        ]);

        $this->upsertRedirect($pathForSlug($oldSlug), $pathForSlug($newSlug), 301, true);
    }

    public function upsertRedirect(
        string $fromPath,
        string $toPath,
        int $statusCode = 301,
        bool $validateRegistryTarget = true
    ): ?SeoEngineRedirect {
        $fromPath = $this->normalizePath($fromPath);
        $toPath = $this->normalizePath($toPath);

        if ($validateRegistryTarget && str_starts_with($toPath, '/rent/')) {
            $toPath = $this->resolveRegistryTarget($toPath);
        }

        if ($fromPath === $toPath) {
            return null;
        }

        if ($this->wouldCreateLoop($fromPath, $toPath)) {
            return null;
        }

        SeoEngineRedirect::query()
            ->where('to_path', $fromPath)
            ->update(['to_path' => $toPath]);

        $redirect = SeoEngineRedirect::query()->updateOrCreate(
            ['from_path' => $fromPath],
            ['to_path' => $toPath, 'status_code' => $statusCode]
        );

        $this->clearPathCache($fromPath);

        return $redirect;
    }

    public function wouldCreateLoop(string $fromPath, string $toPath): bool
    {
        $fromPath = $this->normalizePath($fromPath);
        $toPath = $this->normalizePath($toPath);

        if ($fromPath === $toPath) {
            return true;
        }

        $visited = [$fromPath => true];
        $current = $toPath;

        while ($current) {
            if (isset($visited[$current])) {
                return true;
            }
            $visited[$current] = true;
            $current = SeoEngineRedirect::query()->where('from_path', $current)->value('to_path');
        }

        return SeoEngineRedirect::query()
            ->where('from_path', $toPath)
            ->where('to_path', $fromPath)
            ->exists();
    }

    public function resolve(string $fromPath): ?SeoEngineRedirect
    {
        $fromPath = $this->normalizePath($fromPath);

        return Cache::remember(
            self::CACHE_PREFIX . md5($fromPath),
            self::CACHE_TTL_SECONDS,
            fn () => SeoEngineRedirect::query()->where('from_path', $fromPath)->first()
        );
    }

    public function incrementHit(SeoEngineRedirect $redirect): void
    {
        SeoEngineRedirect::query()
            ->whereKey($redirect->id)
            ->increment('hits');
    }

    public function clearPathCache(string $fromPath): void
    {
        Cache::forget(self::CACHE_PREFIX . md5($this->normalizePath($fromPath)));
    }

    public function clearAllRedirectCache(): void
    {
        // File/redis cache cannot wildcard-forget cheaply; bust on admin save via optimize:clear optional.
    }

    /**
     * Resolve redirect target to an existing seo_engine_pages path, walking up to the nearest parent.
     */
    public function resolveRegistryTarget(string $toPath): string
    {
        $toPath = $this->normalizePath($toPath);

        if (SeoEnginePage::query()->where('path', $toPath)->exists()) {
            return $toPath;
        }

        $segments = array_values(array_filter(explode('/', trim($toPath, '/'))));
        while (count($segments) > 1) {
            array_pop($segments);
            $candidate = '/' . implode('/', $segments) . '/';
            if (SeoEnginePage::query()->where('path', $candidate)->exists()) {
                return $candidate;
            }
        }

        if (count($segments) === 1 && $segments[0] === 'rent') {
            $fallback = SeoEnginePage::query()
                ->where('page_type', 'rent_city')
                ->where('is_indexable', true)
                ->orderByDesc('listing_count')
                ->value('path');
            if ($fallback) {
                return $this->normalizePath($fallback);
            }
        }

        $anyCity = SeoEnginePage::query()
            ->where('page_type', 'rent_city')
            ->orderByDesc('listing_count')
            ->value('path');

        return $anyCity ? $this->normalizePath($anyCity) : '/rent/';
    }

    /** Re-point redirects whose to_path is missing from the page registry. */
    public function repairRegistryTargets(): array
    {
        $fixed = [];
        $redirects = SeoEngineRedirect::query()->orderBy('from_path')->get();

        foreach ($redirects as $redirect) {
            if (! str_starts_with($redirect->to_path, '/rent/')) {
                continue;
            }
            $was = $redirect->to_path;
            $resolved = $this->resolveRegistryTarget($was);
            if ($resolved !== $was) {
                $redirect->update(['to_path' => $resolved]);
                $this->clearPathCache($redirect->from_path);
                $fixed[] = ['from' => $redirect->from_path, 'was' => $was, 'now' => $resolved];
            }
        }

        return $fixed;
    }
}
