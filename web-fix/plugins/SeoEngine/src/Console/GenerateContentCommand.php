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
            if ($key === 'tokens') {
                continue;
            }
            $this->line("  {$key}: {$value}");
        }

        if (! empty($stats['tokens']['total'])) {
            $total = (int) $stats['tokens']['total'];
            $generated = max(1, (int) ($stats['generated'] ?? 0));
            $this->line('  tokens_total: ' . $total);
            $this->line('  tokens_per_page: ' . round($total / $generated));
            // gemini-2.5-flash-lite ~$0.075/1M input, ~$0.30/1M output (approximate)
            $inCost = ($stats['tokens']['prompt'] / 1_000_000) * 0.075;
            $outCost = ($stats['tokens']['completion'] / 1_000_000) * 0.30;
            $this->line('  est_cost_usd: ~' . number_format($inCost + $outCost, 4));
        }

        return ($stats['failed'] ?? 0) > 0 && ($stats['generated'] ?? 0) === 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
