<?php

namespace App\Plugins\SeoEngine\Services;

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

        $this->upsertRedirect($pathForSlug($oldSlug), $pathForSlug($newSlug), 301);
    }

    public function upsertRedirect(string $fromPath, string $toPath, int $statusCode = 301): ?SeoEngineRedirect
    {
        $fromPath = $this->normalizePath($fromPath);
        $toPath = $this->normalizePath($toPath);

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
}
