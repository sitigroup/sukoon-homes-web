<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Models\SeoEngineQaPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SeoEngineQaPageApiController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $category = trim((string) $request->query('category', ''));
        $slug = trim((string) $request->query('slug', ''));

        if ($category === '' || $slug === '') {
            return response()->json(['error' => true, 'message' => 'category and slug required'], 422);
        }

        $cacheKey = 'seo_engine:api:qa:' . $category . ':' . $slug;
        $payload = Cache::remember($cacheKey, 600, function () use ($category, $slug) {
            $page = SeoEngineQaPage::query()
                ->where('category', $category)
                ->where('slug', $slug)
                ->where('status', 'published')
                ->first();

            if (! $page) {
                return null;
            }

            $settings = app(\App\Plugins\SeoEngine\Services\SeoEngineSettingsService::class);
            $siteName = (string) $settings->get('site_name', 'Sukoon Homes');
            $webBase = rtrim((string) $settings->get('site_url', 'https://homes.sukoon.group'), '/');

            return [
                'page' => [
                    'question' => $page->question,
                    'direct_answer' => $page->direct_answer,
                    'body_html' => $page->body_html,
                    'category' => $page->category,
                    'slug' => $page->slug,
                    'path' => $page->publicPath(),
                    'title' => $page->question . ' | ' . $siteName,
                    'meta_description' => mb_substr(strip_tags((string) $page->direct_answer), 0, 155),
                    'updated_at' => optional($page->updated_at)->toIso8601String(),
                ],
                'related_rent_links' => $page->related_rent_links ?? [],
                'breadcrumbs' => [
                    ['label' => 'Home', 'path' => '/'],
                    ['label' => 'Guides', 'path' => '/guides/'],
                    ['label' => ucwords(str_replace('-', ' ', $category)), 'path' => '/guides/' . $category . '/'],
                    ['label' => $page->question, 'path' => $page->publicPath()],
                ],
                'canonical' => $webBase . $page->publicPath(),
            ];
        });

        if ($payload === null) {
            return response()->json(['error' => true, 'message' => 'Guide not found'], 404);
        }

        return response()->json([
            'error' => false,
            'message' => 'Q&A guide fetched successfully',
            'data' => $payload,
        ]);
    }

    public function sitemap(): JsonResponse
    {
        $urls = Cache::remember('seo_engine:api:qa_sitemap', 600, function () {
            return SeoEngineQaPage::query()
                ->where('status', 'published')
                ->orderBy('category')
                ->orderBy('slug')
                ->get(['category', 'slug', 'updated_at'])
                ->map(fn ($p) => [
                    'path' => $p->publicPath(),
                    'category' => $p->category,
                    'slug' => $p->slug,
                    'lastmod' => optional($p->updated_at)->toIso8601String(),
                ])
                ->values()
                ->all();
        });

        return response()->json([
            'error' => false,
            'message' => 'Q&A sitemap URLs fetched successfully',
            'data' => $urls,
        ]);
    }
}
