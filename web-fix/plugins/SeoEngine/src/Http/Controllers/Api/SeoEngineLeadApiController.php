<?php

namespace App\Plugins\SeoEngine\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Plugins\SeoEngine\Services\SeoEngineLeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class SeoEngineLeadApiController extends Controller
{
    public function store(Request $request, SeoEngineLeadService $leads): JsonResponse
    {
        if ($request->filled('website')) {
            return response()->json([
                'error' => false,
                'message' => 'Thank you',
                'data' => ['id' => 0],
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+\-\s()]{8,20}$/'],
            'requirement' => ['nullable', 'string', 'max:500'],
            'source_path' => ['required', 'string', 'max:512'],
            'area_id' => ['nullable', 'integer'],
            'sub_area_id' => ['nullable', 'integer'],
            'form_type' => ['nullable', 'in:lead,alert'],
        ]);

        $key = 'seo-lead:' . sha1($request->ip() . '|' . ($validated['source_path'] ?? ''));
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'error' => true,
                'message' => 'Too many submissions. Please try again later.',
                'data' => null,
            ], 429);
        }
        RateLimiter::hit($key, 3600);

        $lead = $leads->store([
            'name' => trim($validated['name']),
            'phone' => preg_replace('/\s+/', '', trim($validated['phone'])),
            'requirement' => isset($validated['requirement']) ? trim($validated['requirement']) : null,
            'source_path' => $validated['source_path'],
            'area_id' => $validated['area_id'] ?? null,
            'sub_area_id' => $validated['sub_area_id'] ?? null,
            'form_type' => $validated['form_type'] ?? 'lead',
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'error' => false,
            'message' => 'Thank you — our team will contact you shortly.',
            'data' => ['id' => $lead->id],
        ], 201);
    }
}
