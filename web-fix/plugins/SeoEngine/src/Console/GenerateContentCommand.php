<?php

namespace App\Plugins\SeoEngine\Console;

use App\Plugins\SeoEngine\Services\SeoEngineContentService;
use Illuminate\Console\Command;

class GenerateContentCommand extends Command
{
    protected $signature = 'seo-engine:generate-content
                            {--paths= : Comma-separated /rent/ paths to generate}
                            {--limit= : Max pages when no --paths}
                            {--force : Regenerate even if content exists and stats stable}';

    protected $description = 'Generate AI intro_html + FAQ JSON for SEO Engine rent pages';

    public function handle(SeoEngineContentService $content): int
    {
        $pathsOption = $this->option('paths');
        $paths = null;
        if (is_string($pathsOption) && trim($pathsOption) !== '') {
            $paths = array_values(array_filter(array_map(
                fn ($p) => rtrim(trim($p), '/') . '/',
                explode(',', $pathsOption)
            )));
        }

        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $force = (bool) $this->option('force');

        $this->info('Generating SEO content...');
        $stats = $content->generate($paths, $force, $paths === null ? $limit : null);

        foreach ($stats as $key => $value) {
            if ($key === 'paths') {
                $this->line('  paths: ' . implode(', ', $value));

                continue;
            }
            $this->line("  {$key}: {$value}");
        }

        return ($stats['failed'] ?? 0) > 0 && ($stats['generated'] ?? 0) === 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
