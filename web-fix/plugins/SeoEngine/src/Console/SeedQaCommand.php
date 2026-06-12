<?php

namespace App\Plugins\SeoEngine\Console;

use App\Plugins\SeoEngine\Services\SeoEngineQaSeedService;
use Illuminate\Console\Command;

class SeedQaCommand extends Command
{
    protected $signature = 'seo-engine:seed-qa {--force : Re-seed even if rows exist}';

    protected $description = 'Seed 25 draft Q&A guide pages (Rajasthan/Barmer rental topics)';

    public function handle(SeoEngineQaSeedService $seed): int
    {
        $count = $seed->run((bool) $this->option('force'));
        $this->info("Q&A seed complete: {$count} row(s) inserted.");

        return self::SUCCESS;
    }
}
