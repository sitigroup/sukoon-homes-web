<?php

namespace App\Plugins\NearbyPlaces\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Plugins\NearbyPlaces\Models\NearbyCategory;
use App\Plugins\NearbyPlaces\Services\GoogleNearbySearchService;
use App\Plugins\NearbyPlaces\Services\NearbyPlacesService;
use App\Plugins\NearbyPlaces\Support\NearbyPlacesSettings;
use App\Services\HelperService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NearbyPlacesAdminController extends Controller
{
    public function index()
    {
        if (!has_permissions('read', 'web_settings')) {
            return redirect()->back()->with('error', trans(PERMISSION_ERROR_MSG));
        }

        $categories = NearbyCategory::query()->orderBy('sort_order')->get();
        $settings = HelperService::getMultipleSettingData(NearbyPlacesSettings::allKeys());
        $google = new GoogleNearbySearchService();

        return view('nearby-places::admin.index', [
            'categories' => $categories,
            'settings' => $settings,
            'apiKeyMasked' => $google->maskedApiKey(),
            'apiKeyConfigured' => $google->hasApiKey(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $data = $this->validateCategory($request);
        $data['slug'] = $this->uniqueSlug($data['name'], $data['slug'] ?? null);
        $data['is_active'] = $request->boolean('is_active');

        NearbyCategory::create($data);

        return ResponseService::successResponse('Category created successfully');
    }

    public function updateCategory(Request $request, NearbyCategory $category)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $data = $this->validateCategory($request, $category->id);
        $data['slug'] = $this->uniqueSlug($data['name'], $data['slug'] ?? $category->slug, $category->id);
        $data['is_active'] = $request->boolean('is_active');

        $category->update($data);

        return ResponseService::successResponse('Category updated successfully');
    }

    public function destroyCategory(NearbyCategory $category)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $category->delete();

        return ResponseService::successResponse('Category archived successfully');
    }

    public function restoreCategory(int $categoryId)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $category = NearbyCategory::withTrashed()->findOrFail($categoryId);
        $category->restore();

        return ResponseService::successResponse('Category restored successfully');
    }

    public function updateSettings(Request $request)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $request->validate([
            'nearby_places_enabled' => 'nullable|in:0,1',
            'nearby_places_default_radius_m' => 'required|integer|min:100|max:50000',
            'nearby_places_default_max_results' => 'required|integer|min:1|max:20',
            'nearby_places_cache_ttl_hours' => 'required|integer|min:1|max:720',
            'nearby_places_travel_time_enabled' => 'nullable|in:0,1',
            'nearby_places_travel_cache_ttl_hours' => 'required|integer|min:1|max:720',
        ]);

        Setting::updateOrCreate(['type' => NearbyPlacesSettings::ENABLED], ['data' => $request->input('nearby_places_enabled', '0') === '1' ? '1' : '0']);
        Setting::updateOrCreate(['type' => NearbyPlacesSettings::DEFAULT_RADIUS_M], ['data' => (string) $request->input('nearby_places_default_radius_m')]);
        Setting::updateOrCreate(['type' => NearbyPlacesSettings::DEFAULT_MAX_RESULTS], ['data' => (string) $request->input('nearby_places_default_max_results')]);
        Setting::updateOrCreate(['type' => NearbyPlacesSettings::CACHE_TTL_HOURS], ['data' => (string) $request->input('nearby_places_cache_ttl_hours')]);
        Setting::updateOrCreate(['type' => NearbyPlacesSettings::TRAVEL_TIME_ENABLED], ['data' => $request->input('nearby_places_travel_time_enabled', '0') === '1' ? '1' : '0']);
        Setting::updateOrCreate(['type' => NearbyPlacesSettings::TRAVEL_CACHE_TTL_HOURS], ['data' => (string) $request->input('nearby_places_travel_cache_ttl_hours')]);

        return ResponseService::successResponse('Nearby settings updated successfully');
    }

    public function checkApiKey()
    {
        if (!has_permissions('read', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $result = (new GoogleNearbySearchService())->testApiKey();

        return response()->json([
            'error' => !$result['ok'],
            'message' => $result['message'],
            'data' => null,
        ]);
    }

    public function refreshProperty(Request $request, NearbyPlacesService $nearbyPlacesService)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $request->validate(['property_id' => 'required|integer|min:1']);
        $payload = $nearbyPlacesService->getForProperty((int) $request->property_id, true);

        return response()->json([
            'error' => false,
            'message' => 'Nearby places refreshed successfully',
            'data' => $payload,
        ]);
    }

    public function clearPropertyCache(Request $request, NearbyPlacesService $nearbyPlacesService)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $request->validate(['property_id' => 'required|integer|min:1']);
        $counts = $nearbyPlacesService->clearCacheForProperty((int) $request->property_id);

        return ResponseService::successResponse(sprintf(
            'Cleared %d nearby rows and %d travel-time rows for this property.',
            $counts['nearby'],
            $counts['travel']
        ));
    }

    public function clearAllCache(NearbyPlacesService $nearbyPlacesService)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $counts = $nearbyPlacesService->clearAllCache();

        return ResponseService::successResponse(sprintf(
            'Cleared %d nearby rows and %d travel-time rows.',
            $counts['nearby'],
            $counts['travel']
        ));
    }

    protected function validateCategory(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'slug' => ['nullable', 'string', 'max:120', Rule::unique('nearby_categories', 'slug')->ignore($ignoreId)],
            'google_place_type' => 'nullable|string|max:120',
            'icon' => 'nullable|string|max:120',
            'default_radius_m' => 'required|integer|min:100|max:50000',
            'max_results' => 'required|integer|min:1|max:20',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|in:0,1',
        ]);
    }

    protected function uniqueSlug(string $name, ?string $slug = null, ?int $ignoreId = null): string
    {
        $base = Str::slug($slug ?: $name);
        if ($base === '') {
            $base = 'category';
        }

        $candidate = $base;
        $counter = 1;

        while (
            NearbyCategory::withTrashed()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }
}
