<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Models\SeoEnginePage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SeoEngineSitemapApiController extends Controller
{
    public function rentPages(): JsonResponse
    {
        $urls = Cache::remember('seo_engine:api:rent_sitemap', 600, function () {
            $base = rtrim(env('NEXT_PUBLIC_WEB_URL', 'https://homes.sukoon.group'), '/');

            return SeoEnginePage::query()
                ->where('is_indexable', true)
                ->orderBy('path')
                ->get(['path', 'updated_at'])
                ->map(fn ($p) => [
                    'path' => $p->path,
                    'loc' => $base . $p->path,
                    'lastmod' => optional($p->updated_at)->toIso8601String(),
                ])
                ->values()
                ->all();
        });

        return response()->json([
            'error' => false,
            'message' => 'Rent sitemap URLs fetched successfully',
            'data' => $urls,
        ]);
    }
}
