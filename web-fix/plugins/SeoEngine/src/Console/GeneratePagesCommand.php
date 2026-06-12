<?php

namespace App\Plugins\SeoEngine\Console;

use App\Plugins\SeoEngine\Services\SeoEnginePageGeneratorService;
use Illuminate\Console\Command;

class GeneratePagesCommand extends Command
{
    protected $signature = 'seo-engine:generate-pages';

    protected $description = 'Generate /rent/ registry pages, locality stats, and rebuild rent sitemap';

    public function handle(SeoEnginePageGeneratorService $generator): int
    {
        $this->info('Generating SEO Engine pages...');
        $stats = $generator->generate();
        foreach ($stats as $key => $value) {
            $this->line("  {$key}: {$value}");
        }
        $this->call('seo-engine:build-sitemaps');

        return self::SUCCESS;
    }
}
