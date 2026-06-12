<?php

namespace App\Plugins\SeoEngine\Console;

use App\Plugins\SeoEngine\Services\SeoEngineQaSitemapService;
use App\Plugins\SeoEngine\Services\SeoEngineRentSitemapService;
use Illuminate\Console\Command;

class BuildSitemapsCommand extends Command
{
    protected $signature = 'seo-engine:build-sitemaps';

    protected $description = 'Export indexable /rent/ and /guides/ URLs for the web sitemap index';

    public function handle(SeoEngineRentSitemapService $rent, SeoEngineQaSitemapService $qa): int
    {
        $rentResult = $rent->export();
        $qaResult = $qa->export();
        $this->info('Wrote ' . $rentResult['count'] . ' rent URLs to storage/app/seo-engine/rent-pages.json');
        $this->info('Wrote ' . $qaResult['count'] . ' Q&A URLs to storage/app/seo-engine/qa-guides.json');

        return self::SUCCESS;
    }
}
