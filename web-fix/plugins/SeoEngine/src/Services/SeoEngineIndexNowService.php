<?php

namespace App\Plugins\SeoEngine\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SeoEngineIndexNowService
{
    public function pingUrl(string $url): bool
    {
        $key = (string) $this->settings()->get('indexnow_key', '');
        if ($key === '') {
            $this->appendLog(['url' => $url, 'status' => 'skipped', 'reason' => 'no_key']);

            return false;
        }

        $host = parse_url($url, PHP_URL_HOST) ?: 'homes.sukoon.group';
        try {
            $response = Http::timeout(10)->post('https://api.indexnow.org/indexnow', [
                'host' => $host,
                'key' => $key,
                'urlList' => [$url],
            ]);
            $ok = $response->successful();
            $this->appendLog([
                'url' => $url,
                'status' => $ok ? 'ok' : 'failed',
                'code' => $response->status(),
            ]);

            return $ok;
        } catch (\Throwable $e) {
            Log::warning('IndexNow ping failed: ' . $e->getMessage());
            $this->appendLog(['url' => $url, 'status' => 'error', 'reason' => $e->getMessage()]);

            return false;
        }
    }

    private function settings(): SeoEngineSettingsService
    {
        return app(SeoEngineSettingsService::class);
    }

    private function appendLog(array $entry): void
    {
        $settings = $this->settings();
        $log = $settings->get('indexnow_log', []);
        if (! is_array($log)) {
            $log = [];
        }
        $entry['at'] = now()->toIso8601String();
        array_unshift($log, $entry);
        $settings->set('indexnow_log', array_slice($log, 0, 100), 'cron');
    }
}
