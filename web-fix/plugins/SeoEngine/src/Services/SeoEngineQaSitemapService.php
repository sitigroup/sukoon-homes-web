<?php

namespace App\Plugins\SeoEngine\Services;

use App\Plugins\SeoEngine\Models\SeoEngineQaPage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SeoEngineQaSitemapService
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

        $pages = SeoEngineQaPage::query()
            ->where('status', 'published')
            ->orderBy('category')
            ->orderBy('slug')
            ->get(['category', 'slug', 'updated_at']);

        $urls = $pages->map(fn ($p) => [
            'loc' => $webBase . $p->publicPath(),
            'path' => $p->publicPath(),
            'category' => $p->category,
            'slug' => $p->slug,
            'lastmod' => optional($p->updated_at)->toAtomString(),
        ])->values()->all();

        $outDir = storage_path('app/seo-engine');
        File::ensureDirectoryExists($outDir, 0775, true);
        $path = $outDir . '/qa-guides.json';
        $payload = json_encode($urls, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        try {
            File::put($path, $payload);
            @chmod($path, 0664);
        } catch (\Throwable $e) {
            Log::warning('SeoEngine QA sitemap export failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return ['count' => count($urls), 'path' => $path, 'written' => false];
        }

        return ['count' => count($urls), 'path' => $path, 'written' => true];
    }
}
