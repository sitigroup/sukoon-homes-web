<?php

namespace App\Plugins\Theme\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Plugins\Theme\Services\ThemeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ThemeAdminController extends Controller
{
    public function index()
    {
        if (! has_permissions('read', 'web_settings')) {
            return redirect()->back()->with('error', __('Permission Denied'));
        }

        return view('theme::admin.index', ThemeService::adminState());
    }

    public function saveDraft(Request $request)
    {
        if (! has_permissions('update', 'web_settings')) {
            return response()->json(['error' => true, 'message' => __('Permission Denied')], 403);
        }

        $validator = Validator::make($request->all(), [
            'tokens' => 'required|array',
            'tokens.primary' => 'required|string|max:16',
            'tokens.secondary' => 'required|string|max:16',
            'tokens.accent' => 'required|string|max:16',
            'tokens.sidebar' => 'required|string|max:16',
            'tokens.card' => 'required|string|max:16',
            'tokens.border' => 'required|string|max:16',
            'tokens.button' => 'required|string|max:16',
            'tokens.hover' => 'required|string|max:16',
            'tokens.link' => 'required|string|max:16',
            'typography' => 'required|string|max:255',
            'border_radius' => 'required|string|max:16',
            'shadow_intensity' => 'required|in:soft,medium,strong',
            'preset_slug' => 'nullable|string|max:64',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 422);
        }

        $state = ThemeService::saveDraft($request->all(), auth()->id());

        return response()->json(['error' => false, 'message' => 'Draft saved', 'data' => $state]);
    }

    public function publish(Request $request)
    {
        if (! has_permissions('update', 'web_settings')) {
            return response()->json(['error' => true, 'message' => __('Permission Denied')], 403);
        }

        if ($request->filled('tokens')) {
            ThemeService::saveDraft($request->all(), auth()->id());
        }

        $state = ThemeService::publish(auth()->id());

        return response()->json(['error' => false, 'message' => 'Theme published', 'data' => $state]);
    }

    public function unpublish()
    {
        if (! has_permissions('update', 'web_settings')) {
            return response()->json(['error' => true, 'message' => __('Permission Denied')], 403);
        }

        $state = ThemeService::unpublish(auth()->id());

        return response()->json(['error' => false, 'message' => 'Theme unpublished', 'data' => $state]);
    }

    public function resetDefault()
    {
        if (! has_permissions('update', 'web_settings')) {
            return response()->json(['error' => true, 'message' => __('Permission Denied')], 403);
        }

        $state = ThemeService::resetToDefault(auth()->id());

        return response()->json(['error' => false, 'message' => 'Theme reset to default', 'data' => $state]);
    }

    public function applyPreset(Request $request)
    {
        if (! has_permissions('update', 'web_settings')) {
            return response()->json(['error' => true, 'message' => __('Permission Denied')], 403);
        }

        $slug = (string) $request->input('preset_slug', '');
        if ($slug === '') {
            return response()->json(['error' => true, 'message' => 'Preset slug required'], 422);
        }

        try {
            $state = ThemeService::applyPreset($slug, auth()->id());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['error' => false, 'message' => 'Preset applied to draft', 'data' => $state]);
    }

    public function restoreVersion(Request $request)
    {
        if (! has_permissions('update', 'web_settings')) {
            return response()->json(['error' => true, 'message' => __('Permission Denied')], 403);
        }

        $versionId = (int) $request->input('version_id', 0);
        if ($versionId < 1) {
            return response()->json(['error' => true, 'message' => 'Version id required'], 422);
        }

        $state = ThemeService::restoreVersion($versionId, auth()->id());

        return response()->json(['error' => false, 'message' => 'Version restored to draft', 'data' => $state]);
    }

    public function previewPayload()
    {
        if (! has_permissions('read', 'web_settings')) {
            return response()->json(['error' => true, 'message' => __('Permission Denied')], 403);
        }

        return response()->json([
            'error' => false,
            'data' => [
                'draft' => ThemeService::draftPayload(),
                'published' => ThemeService::publishedPayload(),
            ],
        ]);
    }
}
