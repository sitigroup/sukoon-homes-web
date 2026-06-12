<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Services\SeoEngineRedirectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeoEngineRedirectApiController extends Controller
{
    public function show(Request $request, SeoEngineRedirectService $redirects): JsonResponse
    {
        $from = (string) $request->query('from', '');
        if ($from === '') {
            return response()->json([
                'error' => true,
                'message' => 'Missing from parameter',
                'data' => null,
            ], 422);
        }

        $redirect = $redirects->resolve($from);
        if (! $redirect) {
            return response()->json([
                'error' => false,
                'message' => 'No redirect found',
                'data' => null,
            ]);
        }

        $redirects->incrementHit($redirect);

        return response()->json([
            'error' => false,
            'message' => 'Redirect found',
            'data' => [
                'from_path' => $redirect->from_path,
                'to_path' => $redirect->to_path,
                'status_code' => (int) $redirect->status_code,
            ],
        ]);
    }
}
