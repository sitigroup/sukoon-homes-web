<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Services\SeoEngineSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SeoEngineBotFilesApiController extends Controller
{
    /** A1 baseline disallows — always appended. */
    private const A1_DISALLOWS = [
        '/user/',
        '/login',
        '/dashboard',
        '/owner-dashboard',
        '/tenant-dashboard',
        '/payment',
        '/property-detail-preview/',
        '/compare-properties/',
        '/api/',
    ];

    public function robots(SeoEngineSettingsService $settings): JsonResponse
    {
        $body = Cache::remember('seo_engine:api:robots_txt', SeoEngineSettingsService::CACHE_TTL_SECONDS, function () use ($settings) {
            return $this->buildRobotsTxt($settings);
        });

        return response()->json([
            'error' => false,
            'message' => 'robots.txt content',
            'data' => ['body' => $body],
        ]);
    }

    public function llms(SeoEngineSettingsService $settings): JsonResponse
    {
        $body = Cache::remember('seo_engine:api:llms_txt', SeoEngineSettingsService::CACHE_TTL_SECONDS, function () use ($settings) {
            return (string) $settings->get('llms_txt', '');
        });

        return response()->json([
            'error' => false,
            'message' => 'llms.txt content',
            'data' => ['body' => $body],
        ]);
    }

    private function buildRobotsTxt(SeoEngineSettingsService $settings): string
    {
        $lines = [];
        $adminBody = trim((string) $settings->get('robots_txt', ''));
        if ($adminBody !== '') {
            $lines[] = $adminBody;
        } else {
            $lines[] = 'User-agent: *';
            $lines[] = 'Allow: /';
        }

        foreach (self::A1_DISALLOWS as $path) {
            $line = 'Disallow: ' . $path;
            if (! str_contains(implode("\n", $lines), $line)) {
                $lines[] = $line;
            }
        }

        $policy = $settings->get('ai_bot_policy', []);
        if (is_array($policy)) {
            foreach ($policy as $bot => $rule) {
                if ($rule === 'allow') {
                    $lines[] = 'User-agent: ' . $bot;
                    $lines[] = 'Allow: /';
                } elseif ($rule === 'block') {
                    $lines[] = 'User-agent: ' . $bot;
                    $lines[] = 'Disallow: /';
                }
            }
        }

        if (! str_contains(implode("\n", $lines), 'Sitemap:')) {
            $siteUrl = rtrim((string) $settings->get('site_url', 'https://homes.sukoon.group'), '/');
            $lines[] = '';
            $lines[] = 'Sitemap: ' . $siteUrl . '/sitemap.xml';
        }

        return trim(implode("\n", $lines)) . "\n";
    }
}
