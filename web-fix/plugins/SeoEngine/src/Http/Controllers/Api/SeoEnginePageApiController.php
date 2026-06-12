<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Services\SeoEnginePageDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoEnginePageApiController extends Controller
{
    public function show(Request $request, SeoEnginePageDataService $data): JsonResponse
    {
        $path = (string) $request->query('path', '');
        if ($path === '') {
            return response()->json([
                'error' => true,
                'message' => 'Missing path parameter',
                'data' => null,
            ], 422);
        }

        $payload = $data->getByPath($path);
        if (! $payload) {
            return response()->json([
                'error' => true,
                'message' => 'Page not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'error' => false,
            'message' => 'Page data fetched successfully',
            'data' => $payload,
        ]);
    }

    public function paths(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->query('per_page', 25)));
        $indexableOnly = $request->boolean('indexable');

        $query = SeoEnginePage::query()
            ->when($indexableOnly, fn ($q) => $q->where('is_indexable', true))
            ->when($request->filled('type'), fn ($q) => $q->where('page_type', $request->query('type')))
            ->orderByDesc('listing_count')
            ->orderBy('path');

        $paginator = $query->paginate($perPage, [
            'path', 'page_type', 'title', 'listing_count', 'is_indexable', 'updated_at',
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Paths fetched successfully',
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
