<?php

namespace App\Plugins\SeoEngine\Console;

use App\Plugins\SeoEngine\Services\SeoEngineRentSitemapService;
use Illuminate\Console\Command;

class BuildSitemapsCommand extends Command
{
    protected $signature = 'seo-engine:build-sitemaps';

    protected $description = 'Export indexable /rent/ URLs for the web sitemap index';

    public function handle(SeoEngineRentSitemapService $exporter): int
    {
        $result = $exporter->export();
        $this->info('Wrote ' . $result['count'] . ' rent URLs to storage/app/seo-engine/rent-pages.json');

        return self::SUCCESS;
    }
}
