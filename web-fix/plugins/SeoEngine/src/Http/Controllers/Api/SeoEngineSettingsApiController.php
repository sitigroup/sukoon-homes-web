<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Services\SeoEngineSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SeoEngineSettingsApiController extends Controller
{
    public function show(SeoEngineSettingsService $settings): JsonResponse
    {
        $payload = Cache::remember('seo_engine:api:public_settings', SeoEngineSettingsService::CACHE_TTL_SECONDS, function () use ($settings) {
            return $settings->publicSubset();
        });

        return response()->json([
            'error' => false,
            'message' => 'SEO Engine settings fetched successfully',
            'data' => $payload,
        ]);
    }
}
