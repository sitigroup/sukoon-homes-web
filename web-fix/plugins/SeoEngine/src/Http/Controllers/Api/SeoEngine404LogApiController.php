<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Models\SeoEngine404Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoEngine404LogApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $path = (string) $request->input('path', '');
        if ($path === '') {
            return response()->json(['error' => true, 'message' => 'Missing path'], 422);
        }

        $row = SeoEngine404Log::query()->firstOrNew(['path' => $path]);
        $row->referrer = (string) $request->input('referrer', '');
        $row->user_agent = (string) $request->header('User-Agent', '');
        $row->ip = (string) $request->ip();
        $row->hit_count = (int) ($row->hit_count ?? 0) + 1;
        $row->last_seen_at = now();
        $row->save();

        return response()->json(['error' => false, 'message' => 'Logged']);
    }
}
