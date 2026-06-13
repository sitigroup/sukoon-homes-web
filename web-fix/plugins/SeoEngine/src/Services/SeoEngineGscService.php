<?php

namespace App\Plugins\SeoEngine\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SeoEngineGscService
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';

    public function __construct(
        private SeoEngineSettingsService $settings
    ) {
    }

    public function isConfigured(): bool
    {
        return trim((string) $this->settings->get('gsc_property', '')) !== ''
            && trim((string) $this->settings->get('gsc_client_id', '')) !== ''
            && trim((string) $this->settings->get('gsc_client_secret', '')) !== '';
    }

    public function isConnected(): bool
    {
        return $this->isConfigured() && trim((string) $this->settings->get('gsc_refresh_token', '')) !== '';
    }

    public function authUrl(string $redirectUri, string $state): string
    {
        $params = http_build_query([
            'client_id' => (string) $this->settings->get('gsc_client_id', ''),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return self::AUTH_URL . '?' . $params;
    }

    public function exchangeCode(string $code, string $redirectUri): bool
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => (string) $this->settings->get('gsc_client_id', ''),
            'client_secret' => (string) $this->settings->get('gsc_client_secret', ''),
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (! $response->successful()) {
            return false;
        }

        $json = $response->json();
        if (! empty($json['refresh_token'])) {
            $this->settings->set('gsc_refresh_token', (string) $json['refresh_token'], 'secrets');
        }
        if (! empty($json['access_token'])) {
            $this->settings->set('gsc_access_token', (string) $json['access_token'], 'secrets');
            $this->settings->set('gsc_access_expires_at', now()->addSeconds((int) ($json['expires_in'] ?? 3600))->toIso8601String(), 'secrets');
        }

        return true;
    }

    public function disconnect(): void
    {
        $this->settings->set('gsc_refresh_token', '', 'secrets');
        $this->settings->set('gsc_access_token', '', 'secrets');
        $this->settings->set('gsc_access_expires_at', null, 'secrets');
        $this->settings->set('gsc_cache', [], 'analytics');
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardMetrics(): array
    {
        if (! $this->isConnected()) {
            return ['connected' => false, 'awaiting' => true];
        }

        $cache = (array) $this->settings->get('gsc_cache', []);
        if (($cache['date'] ?? '') === now()->toDateString() && ! empty($cache['dashboard'])) {
            return array_merge(['connected' => true, 'awaiting' => false], $cache['dashboard']);
        }

        $synced = $this->syncDaily();

        return array_merge(['connected' => true, 'awaiting' => ! $synced], $synced ? (array) ($this->settings->get('gsc_cache')['dashboard'] ?? []) : []);
    }

    /**
     * @return array{clicks:int,impressions:int,position:float|null}|null
     */
    public function pageMetrics(string $path): ?array
    {
        $cache = (array) $this->settings->get('gsc_cache', []);
        $pages = (array) ($cache['pages'] ?? []);

        return $pages[$path] ?? null;
    }

    public function syncDaily(): bool
    {
        if (! $this->isConnected()) {
            return false;
        }

        $token = $this->accessToken();
        if ($token === '') {
            return false;
        }

        $site = rawurlencode((string) $this->settings->get('gsc_property', ''));
        $end = now()->subDay()->toDateString();
        $start = now()->subDays(28)->toDateString();

        try {
            $summary = $this->queryAnalytics($token, $site, $start, $end, []);
            $queries = $this->queryAnalytics($token, $site, $start, $end, ['query'], 10);
            $pages = $this->queryAnalytics($token, $site, $start, $end, ['page'], 50);
            $prevStart = now()->subDays(56)->toDateString();
            $prevEnd = now()->subDays(29)->toDateString();
            $prevPages = $this->queryAnalytics($token, $site, $prevStart, $prevEnd, ['page'], 50);

            $pageMap = [];
            $movers = [];
            foreach ($pages['rows'] ?? [] as $row) {
                $url = (string) ($row['keys'][0] ?? '');
                $path = $this->urlToRentPath($url);
                if ($path === null) {
                    continue;
                }
                $pageMap[$path] = [
                    'clicks' => (int) ($row['clicks'] ?? 0),
                    'impressions' => (int) ($row['impressions'] ?? 0),
                    'position' => isset($row['position']) ? round((float) $row['position'], 1) : null,
                ];
            }

            $prevMap = [];
            foreach ($prevPages['rows'] ?? [] as $row) {
                $path = $this->urlToRentPath((string) ($row['keys'][0] ?? ''));
                if ($path !== null) {
                    $prevMap[$path] = isset($row['position']) ? round((float) $row['position'], 1) : null;
                }
            }

            foreach ($pageMap as $path => $metrics) {
                $prevPos = $prevMap[$path] ?? null;
                if ($prevPos !== null && $metrics['position'] !== null) {
                    $delta = round($prevPos - $metrics['position'], 1);
                    if (abs($delta) >= 2) {
                        $movers[] = array_merge(['path' => $path, 'delta' => $delta], $metrics);
                    }
                }
            }

            usort($movers, fn ($a, $b) => abs($b['delta']) <=> abs($a['delta']));

            $lowCtr = [];
            foreach ($pageMap as $path => $metrics) {
                if ($metrics['impressions'] >= 20 && $metrics['clicks'] === 0) {
                    $lowCtr[] = array_merge(['path' => $path], $metrics);
                }
            }

            $dashboard = [
                'period' => ['start' => $start, 'end' => $end],
                'totals' => [
                    'clicks' => (int) ($summary['rows'][0]['clicks'] ?? 0),
                    'impressions' => (int) ($summary['rows'][0]['impressions'] ?? 0),
                    'position' => isset($summary['rows'][0]['position']) ? round((float) $summary['rows'][0]['position'], 1) : null,
                ],
                'top_queries' => array_map(fn ($row) => [
                    'query' => $row['keys'][0] ?? '',
                    'clicks' => (int) ($row['clicks'] ?? 0),
                    'impressions' => (int) ($row['impressions'] ?? 0),
                    'position' => isset($row['position']) ? round((float) $row['position'], 1) : null,
                ], $queries['rows'] ?? []),
                'top_pages' => array_slice(array_map(fn ($row) => [
                    'path' => $this->urlToRentPath((string) ($row['keys'][0] ?? '')) ?? (string) ($row['keys'][0] ?? ''),
                    'clicks' => (int) ($row['clicks'] ?? 0),
                    'impressions' => (int) ($row['impressions'] ?? 0),
                    'position' => isset($row['position']) ? round((float) $row['position'], 1) : null,
                ], $pages['rows'] ?? []), 0, 10),
                'movers' => array_slice($movers, 0, 10),
                'low_ctr' => array_slice($lowCtr, 0, 10),
                'synced_at' => now()->toIso8601String(),
            ];

            $this->settings->set('gsc_cache', [
                'date' => now()->toDateString(),
                'dashboard' => $dashboard,
                'pages' => $pageMap,
            ], 'analytics');

            $this->settings->set('cron_last_gsc_sync_at', now()->toIso8601String(), 'cron');

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    private function accessToken(): string
    {
        $expires = (string) $this->settings->get('gsc_access_expires_at', '');
        $access = (string) $this->settings->get('gsc_access_token', '');
        if ($access !== '' && $expires !== '' && \Illuminate\Support\Carbon::parse($expires)->isFuture()) {
            return $access;
        }

        $refresh = (string) $this->settings->get('gsc_refresh_token', '');
        if ($refresh === '') {
            return '';
        }

        $response = Http::asForm()->post(self::TOKEN_URL, [
            'client_id' => (string) $this->settings->get('gsc_client_id', ''),
            'client_secret' => (string) $this->settings->get('gsc_client_secret', ''),
            'refresh_token' => $refresh,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            return '';
        }

        $json = $response->json();
        $access = (string) ($json['access_token'] ?? '');
        if ($access !== '') {
            $this->settings->set('gsc_access_token', $access, 'secrets');
            $this->settings->set('gsc_access_expires_at', now()->addSeconds((int) ($json['expires_in'] ?? 3600))->toIso8601String(), 'secrets');
        }

        return $access;
    }

    /**
     * @param  list<string>  $dimensions
     * @return array<string, mixed>
     */
    private function queryAnalytics(string $token, string $site, string $start, string $end, array $dimensions, int $rowLimit = 1): array
    {
        $response = Http::withToken($token)->post(
            'https://www.googleapis.com/webmasters/v3/sites/' . $site . '/searchAnalytics/query',
            [
                'startDate' => $start,
                'endDate' => $end,
                'dimensions' => $dimensions,
                'rowLimit' => $rowLimit,
                'dataState' => 'all',
            ]
        );

        if (! $response->successful()) {
            return ['rows' => []];
        }

        return $response->json();
    }

    private function urlToRentPath(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || ! str_starts_with($path, '/rent/')) {
            return null;
        }

        return rtrim($path, '/') . '/';
    }
}
