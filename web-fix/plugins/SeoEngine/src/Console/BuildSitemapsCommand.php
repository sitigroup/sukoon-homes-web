<?php

namespace App\Plugins\SeoEngine\Console;

use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Services\SeoEngineSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BuildSitemapsCommand extends Command
{
    protected $signature = 'seo-engine:build-sitemaps';

    protected $description = 'Export indexable /rent/ URLs for the web sitemap index';

    public function handle(SeoEngineSettingsService $settings): int
    {
        $webBase = rtrim(env('NEXT_PUBLIC_WEB_URL', 'https://homes.sukoon.group'), '/');
        $pages = SeoEnginePage::query()
            ->where('is_indexable', true)
            ->orderBy('path')
            ->get(['path', 'updated_at']);

        $urls = $pages->map(fn ($p) => [
            'loc' => $webBase . $p->path,
            'lastmod' => optional($p->updated_at)->toAtomString(),
        ])->values()->all();

        $outDir = storage_path('app/seo-engine');
        File::ensureDirectoryExists($outDir);
        File::put($outDir . '/rent-pages.json', json_encode($urls, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $settings->set('cron_last_build_sitemaps_at', now()->toIso8601String(), 'cron');
        $this->info('Wrote ' . count($urls) . ' rent URLs to storage/app/seo-engine/rent-pages.json');

        return self::SUCCESS;
    }
}
