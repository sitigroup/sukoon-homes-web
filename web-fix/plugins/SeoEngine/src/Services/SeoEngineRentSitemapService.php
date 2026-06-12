<?php

namespace App\Plugins\SeoEngine\Services;

use App\Plugins\SeoEngine\Models\SeoEnginePage;
use Illuminate\Support\Facades\File;

class SeoEngineRentSitemapService
{
    public function __construct(private SeoEngineSettingsService $settings)
    {
    }

    /**
     * @return array{count:int,path:string}
     */
    public function export(): array
    {
        $webBase = rtrim(
            (string) ($this->settings->get('site_url') ?: env('NEXT_PUBLIC_WEB_URL', 'https://homes.sukoon.group')),
            '/'
        );

        $pages = SeoEnginePage::query()
            ->where('is_indexable', true)
            ->orderBy('path')
            ->get(['path', 'updated_at']);

        $urls = $pages->map(fn ($p) => [
            'loc' => $webBase . $p->path,
            'lastmod' => optional($p->updated_at)->toAtomString(),
        ])->values()->all();

        $outDir = storage_path('app/seo-engine');
        File::ensureDirectoryExists($outDir, 0775, true);
        $path = $outDir . '/rent-pages.json';
        File::put($path, json_encode($urls, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->settings->set('cron_last_build_sitemaps_at', now()->toIso8601String(), 'cron');

        return ['count' => count($urls), 'path' => $path];
    }
}
