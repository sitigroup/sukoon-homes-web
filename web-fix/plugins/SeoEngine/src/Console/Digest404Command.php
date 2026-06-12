<?php

namespace App\Plugins\SeoEngine\Console;

use App\Plugins\SeoEngine\Models\SeoEngine404Log;
use App\Plugins\SeoEngine\Services\SeoEngineSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Digest404Command extends Command
{
    protected $signature = 'seo-engine:digest-404 {--days=7 : Lookback window in days}';

    protected $description = 'Log weekly 404 digest summary for rent/guide paths';

    public function handle(SeoEngineSettingsService $settings): int
    {
        $days = max(1, (int) $this->option('days'));
        $since = now()->subDays($days);

        $rows = SeoEngine404Log::query()
            ->where('last_seen_at', '>=', $since)
            ->orderByDesc('hit_count')
            ->limit(50)
            ->get(['path', 'hit_count', 'last_seen_at']);

        $digest = [
            'period_days' => $days,
            'total_paths' => $rows->count(),
            'total_hits' => (int) $rows->sum('hit_count'),
            'top' => $rows->take(20)->map(fn ($r) => [
                'path' => $r->path,
                'hits' => $r->hit_count,
                'last_seen' => optional($r->last_seen_at)->toIso8601String(),
            ])->values()->all(),
            'generated_at' => now()->toIso8601String(),
        ];

        $settings->set('cron_last_404_digest_at', $digest['generated_at'], 'cron');
        $settings->set('404_digest_latest', $digest, 'cron');

        Log::info('SeoEngine 404 digest', $digest);
        $this->info('404 digest: ' . $digest['total_paths'] . ' paths, ' . $digest['total_hits'] . ' hits (last ' . $days . ' days)');

        foreach ($digest['top'] as $row) {
            $this->line('  ' . $row['hits'] . 'x ' . $row['path']);
        }

        return self::SUCCESS;
    }
}
