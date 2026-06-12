<?php

namespace App\Plugins\NearbyPlaces\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Setting;
use App\Plugins\NearbyPlaces\Models\NearbyCategory;
use App\Plugins\NearbyPlaces\Models\NearbyPlaceOverride;
use App\Plugins\NearbyPlaces\Services\GoogleNearbySearchService;
use App\Plugins\NearbyPlaces\Services\NearbyPlaceOverrideService;
use App\Plugins\NearbyPlaces\Services\NearbyPlacesService;
use App\Plugins\NearbyPlaces\Support\NearbyPlacesSettings;
use App\Services\HelperService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NearbyPlacesAdminController extends Controller
{
    public function index()
    {
        if (!has_permissions('read', 'web_settings')) {
            return redirect()->back()->with('error', trans(PERMISSION_ERROR_MSG));
        }

        $settings = HelperService::getMultipleSettingData(NearbyPlacesSettings::allKeys());
        $google = new GoogleNearbySearchService();

        return view('nearby-places::admin.index', [
            'activeCategories' => NearbyCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'archivedCategories' => NearbyCategory::onlyTrashed()->orderBy('sort_order')->orderBy('name')->get(),
            'stats' => $this->buildDashboardStats(),
            'settings' => $settings,
            'apiKeyMasked' => $google->maskedApiKey(),
            'apiKeyConfigured' => $google->hasApiKey(),
            'pluginEnabled' => NearbyPlacesSettings::isEnabled(),
            'travelTimeEnabled' => NearbyPlacesSettings::travelTimeEnabled(),
            'qualityFilterEnabled' => NearbyPlacesSettings::qualityFilterEnabled(),
            'cachedProperties' => $this->loadCachedProperties(),
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
        $data = $this->normalizeCategoryQualityFields($data, $request);
        $data = $this->normalizeCategoryCacheTtlFields($data, $request);
        $data['icon'] = NearbyCategory::normalizeIconClass($data['icon'] ?? null);
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
        $data = $this->normalizeCategoryQualityFields($data, $request);
        $data = $this->normalizeCategoryCacheTtlFields($data, $request);
        $data['icon'] = NearbyCategory::normalizeIconClass($data['icon'] ?? null);

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

    public function bulkStoreCategories(Request $request)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $request->validate([
            'categories' => 'required|array|min:1|max:50',
            'categories.*.name' => 'required|string|max:120',
            'categories.*.slug' => 'nullable|string|max:120',
            'categories.*.google_place_type' => 'nullable|string|max:120',
            'categories.*.icon' => 'nullable|string|max:120',
            'categories.*.default_radius_m' => 'nullable|integer|min:100|max:50000',
            'categories.*.max_results' => 'nullable|integer|min:1|max:20',
            'categories.*.sort_order' => 'nullable|integer|min:0|max:9999',
            'categories.*.is_active' => 'nullable|in:0,1',
        ]);

        $defaults = [
            'default_radius_m' => (int) NearbyPlacesSettings::defaultRadiusM(),
            'max_results' => (int) NearbyPlacesSettings::defaultMaxResults(),
            'sort_order' => 0,
            'is_active' => true,
        ];

        $created = 0;
        $errors = [];

        foreach ($request->input('categories', []) as $index => $row) {
            try {
                $payload = array_merge($defaults, $row);
                $payload['slug'] = $this->uniqueSlug($payload['name'], $payload['slug'] ?? null);
                $payload['is_active'] = filter_var($payload['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);
                $payload['icon'] = NearbyCategory::normalizeIconClass($payload['icon'] ?? null);
                $payload = $this->normalizeCategoryQualityFields($payload, new Request($payload));
                $payload = $this->normalizeCategoryCacheTtlFields($payload, new Request($payload));
                NearbyCategory::create($payload);
                $created++;
            } catch (\Throwable $e) {
                $errors[] = sprintf('Row %d (%s): %s', $index + 1, $row['name'] ?? '—', $e->getMessage());
            }
        }

        if ($created === 0) {
            return ResponseService::errorResponse($errors[0] ?? 'No categories were created.');
        }

        $message = sprintf('%d categor(ies) created successfully.', $created);
        if ($errors !== []) {
            $message .= ' ' . implode(' ', $errors);
        }

        return response()->json([
            'error' => false,
            'message' => $message,
            'data' => ['created' => $created, 'errors' => $errors],
        ]);
    }

    public function bulkArchiveCategories(Request $request)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|min:1',
        ]);

        $archived = NearbyCategory::query()
            ->whereIn('id', $request->input('ids', []))
            ->delete();

        return ResponseService::successResponse(sprintf('%d categor(ies) archived.', $archived));
    }

    public function bulkRestoreCategories(Request $request)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|min:1',
        ]);

        $restored = NearbyCategory::withTrashed()
            ->whereIn('id', $request->input('ids', []))
            ->restore();

        return ResponseService::successResponse(sprintf('%d categor(ies) restored.', $restored));
    }

    public function bulkPatchCategories(Request $request)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|min:1',
            'fields' => 'required|array',
            'fields.default_radius_m' => 'nullable|integer|min:100|max:50000',
            'fields.max_results' => 'nullable|integer|min:1|max:20',
            'fields.min_rating' => 'nullable|numeric|min:0|max:5',
            'fields.min_reviews' => 'nullable|integer|min:0|max:10000',
            'fields.max_results_after_filter' => 'nullable|integer|min:1|max:20',
            'fields.sort_order' => 'nullable|integer|min:0|max:9999',
            'fields.is_active' => 'nullable|in:0,1',
            'fields.cache_ttl_value' => 'nullable|integer|min:1|max:8760',
            'fields.cache_ttl_unit' => 'nullable|in:hours,days',
        ]);

        $inputFields = $request->input('fields', []);
        if ($inputFields === []) {
            return ResponseService::errorResponse('Choose at least one field to update.');
        }

        $patch = [];

        if (array_key_exists('default_radius_m', $inputFields) && $inputFields['default_radius_m'] !== '') {
            $patch['default_radius_m'] = (int) $inputFields['default_radius_m'];
        }
        if (array_key_exists('max_results', $inputFields) && $inputFields['max_results'] !== '') {
            $patch['max_results'] = (int) $inputFields['max_results'];
        }
        if (array_key_exists('min_rating', $inputFields) && $inputFields['min_rating'] !== '') {
            $patch['min_rating'] = (float) $inputFields['min_rating'];
        }
        if (array_key_exists('min_reviews', $inputFields) && $inputFields['min_reviews'] !== '') {
            $patch['min_reviews'] = (int) $inputFields['min_reviews'];
        }
        if (array_key_exists('max_results_after_filter', $inputFields) && $inputFields['max_results_after_filter'] !== '') {
            $patch['max_results_after_filter'] = (int) $inputFields['max_results_after_filter'];
        }
        if (array_key_exists('sort_order', $inputFields) && $inputFields['sort_order'] !== '') {
            $patch['sort_order'] = (int) $inputFields['sort_order'];
        }
        if (array_key_exists('is_active', $inputFields) && $inputFields['is_active'] !== '') {
            $patch['is_active'] = (string) $inputFields['is_active'] === '1';
        }
        if (array_key_exists('cache_ttl_value', $inputFields)) {
            $cachePatch = $this->normalizeCategoryCacheTtlFields([], $request);
            $patch['cache_ttl_hours'] = $cachePatch['cache_ttl_hours'];
        }

        if ($patch === []) {
            return ResponseService::errorResponse('Choose at least one field to update.');
        }

        $updated = NearbyCategory::query()
            ->whereIn('id', $request->input('ids', []))
            ->update($patch);

        return ResponseService::successResponse(sprintf('%d categor(ies) updated.', $updated));
    }

    public function quickUpdateCategories(Request $request)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $request->validate([
            'categories' => 'required|array|min:1',
            'categories.*.id' => 'required|integer|min:1',
            'categories.*.name' => 'required|string|max:120',
            'categories.*.icon' => 'nullable|string|max:120',
            'categories.*.google_place_type' => 'nullable|string|max:120',
            'categories.*.default_radius_m' => 'required|integer|min:100|max:50000',
            'categories.*.max_results' => 'required|integer|min:1|max:20',
            'categories.*.min_rating' => 'nullable|numeric|min:0|max:5',
            'categories.*.max_results_after_filter' => 'nullable|integer|min:1|max:20',
            'categories.*.sort_order' => 'nullable|integer|min:0|max:9999',
            'categories.*.is_active' => 'required|in:0,1',
        ]);

        $updated = 0;

        foreach ($request->input('categories', []) as $row) {
            $category = NearbyCategory::query()->find((int) $row['id']);
            if (!$category) {
                continue;
            }

            $category->update([
                'name' => (string) $row['name'],
                'icon' => NearbyCategory::normalizeIconClass($row['icon'] ?? null),
                'google_place_type' => ($row['google_place_type'] ?? '') !== '' ? (string) $row['google_place_type'] : null,
                'default_radius_m' => (int) $row['default_radius_m'],
                'max_results' => (int) $row['max_results'],
                'min_rating' => isset($row['min_rating']) ? (float) $row['min_rating'] : ($category->min_rating ?? 3.5),
                'max_results_after_filter' => isset($row['max_results_after_filter']) && $row['max_results_after_filter'] !== ''
                    ? (int) $row['max_results_after_filter']
                    : null,
                'sort_order' => isset($row['sort_order']) ? (int) $row['sort_order'] : 0,
                'is_active' => ((string) ($row['is_active'] ?? '1')) === '1',
            ]);
            $updated++;
        }

        return ResponseService::successResponse(sprintf('%d categor(ies) saved.', $updated));
    }

    public function updateSettings(Request $request)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $request->validate([
            'nearby_places_enabled' => 'nullable|in:0,1',
            'nearby_places_quality_filter_enabled' => 'nullable|in:0,1',
            'nearby_places_public_directions_enabled' => 'nullable|in:0,1',
            'nearby_places_default_radius_m' => 'required|integer|min:100|max:50000',
            'nearby_places_default_max_results' => 'required|integer|min:1|max:20',
            'nearby_places_cache_ttl_hours' => 'required|integer|min:1|max:720',
            'nearby_places_travel_time_enabled' => 'nullable|in:0,1',
            'nearby_places_travel_cache_ttl_hours' => 'required|integer|min:1|max:720',
        ]);

        Setting::updateOrCreate(['type' => NearbyPlacesSettings::ENABLED], ['data' => $request->input('nearby_places_enabled', '0') === '1' ? '1' : '0']);
        Setting::updateOrCreate(['type' => NearbyPlacesSettings::QUALITY_FILTER_ENABLED], ['data' => $request->input('nearby_places_quality_filter_enabled', '1') === '1' ? '1' : '0']);
        Setting::updateOrCreate(['type' => NearbyPlacesSettings::PUBLIC_DIRECTIONS_ENABLED], ['data' => $request->input('nearby_places_public_directions_enabled', '0') === '1' ? '1' : '0']);
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

        $request->validate([
            'property_id' => 'required|integer|min:1',
            'category_id' => 'nullable|integer|min:1',
        ]);

        $categoryId = $request->filled('category_id') ? (int) $request->category_id : null;
        $payload = $nearbyPlacesService->getForProperty((int) $request->property_id, true, true, $categoryId);

        $message = $categoryId
            ? 'Nearby places refreshed for the selected category'
            : 'Nearby places refreshed successfully';

        return response()->json([
            'error' => false,
            'message' => $message,
            'data' => $payload,
        ]);
    }

    public function clearPropertyCache(Request $request, NearbyPlacesService $nearbyPlacesService)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $request->validate([
            'property_id' => 'required|integer|min:1',
            'category_id' => 'nullable|integer|min:1',
        ]);

        $categoryId = $request->filled('category_id') ? (int) $request->category_id : null;
        $counts = $nearbyPlacesService->clearCacheForProperty((int) $request->property_id, $categoryId);

        if ($categoryId) {
            return ResponseService::successResponse(sprintf(
                'Cleared %d nearby rows for the selected category on this property.',
                $counts['nearby']
            ));
        }

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

    public function refreshAllCached(Request $request, NearbyPlacesService $nearbyPlacesService)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $request->validate(['category_id' => 'nullable|integer|min:1']);
        $categoryId = $request->filled('category_id') ? (int) $request->category_id : null;
        $propertyIds = $this->cachedPropertyIds();

        if ($propertyIds === []) {
            return ResponseService::successResponse('No cached properties found to refresh.');
        }

        $refreshed = 0;
        $skipped = 0;

        foreach ($propertyIds as $propertyId) {
            $property = Property::query()->find($propertyId);
            if (!$property || !$property->latitude || !$property->longitude) {
                $skipped++;
                continue;
            }

            $nearbyPlacesService->getForProperty($propertyId, true, false, $categoryId);
            $refreshed++;
        }

        $message = sprintf('Refreshed nearby cache for %d propert(ies).', $refreshed);
        if ($skipped > 0) {
            $message .= sprintf(' Skipped %d without coordinates.', $skipped);
        }

        return response()->json([
            'error' => false,
            'message' => $message,
            'data' => [
                'refreshed' => $refreshed,
                'skipped' => $skipped,
                'total_cached' => count($propertyIds),
            ],
        ]);
    }

    public function reviewPlaces(Request $request, NearbyPlacesService $nearbyPlacesService)
    {
        if (!has_permissions('read', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $request->validate([
            'scope' => 'nullable|in:single,all',
            'property_id' => 'nullable|integer|min:1',
            'category_id' => 'nullable|integer|min:1',
            'status' => 'nullable|string|max:40',
            'q' => 'nullable|string|max:120',
        ]);

        $scope = $request->input('scope', 'single');
        $filters = [
            'status' => $request->input('status', 'all'),
            'q' => $request->input('q', ''),
        ];
        $categoryId = $request->filled('category_id') ? (int) $request->category_id : null;

        if ($scope === 'all') {
            $propertyIds = $this->cachedPropertyIds();
            if ($propertyIds === []) {
                return response()->json([
                    'error' => false,
                    'message' => 'No cached properties found',
                    'data' => [
                        'scope' => 'all',
                        'property_count' => 0,
                        'properties' => [],
                        'items' => [],
                        'totals' => ['all' => 0, 'visible' => 0, 'hidden' => 0],
                    ],
                ]);
            }

            $data = $nearbyPlacesService->buildAdminReviewAll($propertyIds, $categoryId, $filters);
        } else {
            if (!$request->filled('property_id')) {
                return ResponseService::errorResponse('Property ID is required for single-property review.');
            }

            $data = $nearbyPlacesService->buildAdminReview(
                (int) $request->property_id,
                $categoryId,
                $filters
            );
            $data['scope'] = 'single';
        }

        if (isset($data['error'])) {
            return ResponseService::errorResponse((string) $data['error']);
        }

        return response()->json([
            'error' => false,
            'message' => 'Review loaded',
            'data' => $data,
        ]);
    }

    public function quickOverride(Request $request, NearbyPlaceOverrideService $overrideService)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $request->validate([
            'action' => ['required', Rule::in([
                'force_show', 'force_hide', 'trust', 'untrust',
                'blacklist', 'whitelist', 'clear_blacklist', 'clear_force_hide',
            ])],
            'property_id' => 'nullable|integer|min:1',
            'category_id' => 'nullable|integer|min:1',
            'google_place_id' => 'nullable|string|max:120',
            'name' => 'nullable|string|max:255',
            'is_recommended' => 'nullable|in:0,1',
            'display_order' => 'nullable|integer|min:0|max:9999',
            'admin_note' => 'nullable|string|max:2000',
        ]);

        $action = (string) $request->action;
        $userId = auth()->id();

        if (in_array($action, ['untrust', 'clear_blacklist', 'clear_force_hide'], true)) {
            $deleteAction = match ($action) {
                'untrust' => NearbyPlaceOverride::ACTION_TRUST,
                'clear_blacklist' => NearbyPlaceOverride::ACTION_BLACKLIST,
                'clear_force_hide' => NearbyPlaceOverride::ACTION_FORCE_HIDE,
            };

            NearbyPlaceOverride::query()
                ->when($request->filled('property_id'), fn ($q) => $q->where('property_id', (int) $request->property_id), fn ($q) => $q->whereNull('property_id'))
                ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->category_id))
                ->when($request->filled('google_place_id'), fn ($q) => $q->where('google_place_id', (string) $request->google_place_id))
                ->where('action', $deleteAction)
                ->delete();

            return ResponseService::successResponse('Override removed');
        }

        $saveAction = match ($action) {
            'force_show' => NearbyPlaceOverride::ACTION_FORCE_SHOW,
            'force_hide' => NearbyPlaceOverride::ACTION_FORCE_HIDE,
            'trust' => NearbyPlaceOverride::ACTION_TRUST,
            'blacklist' => NearbyPlaceOverride::ACTION_BLACKLIST,
            'whitelist' => NearbyPlaceOverride::ACTION_WHITELIST,
        };

        $override = NearbyPlaceOverride::updateOrCreate(
            [
                'property_id' => $request->filled('property_id') ? (int) $request->property_id : null,
                'category_id' => $request->filled('category_id') ? (int) $request->category_id : null,
                'google_place_id' => $request->google_place_id ?: null,
                'action' => $saveAction,
            ],
            [
                'normalized_name' => $request->name ? $overrideService->normalizeName((string) $request->name) : null,
                'display_name' => $request->name ?: null,
                'is_recommended' => $request->boolean('is_recommended'),
                'display_order' => $request->filled('display_order') ? (int) $request->display_order : null,
                'admin_note' => $request->admin_note,
                'updated_by' => $userId,
                'created_by' => $userId,
            ]
        );

        if (!$override->wasRecentlyCreated && !$override->created_by) {
            $override->created_by = $userId;
            $override->save();
        }

        return ResponseService::successResponse('Override saved');
    }

    public function storeOverride(Request $request, NearbyPlaceOverrideService $overrideService)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $data = $request->validate([
            'id' => 'nullable|integer|min:1',
            'property_id' => 'nullable|integer|min:1',
            'category_id' => 'nullable|integer|min:1',
            'google_place_id' => 'nullable|string|max:120',
            'name' => 'nullable|string|max:255',
            'action' => ['required', Rule::in(NearbyPlaceOverride::ACTIONS)],
            'display_name' => 'nullable|string|max:255',
            'display_distance_text' => 'nullable|string|max:120',
            'display_order' => 'nullable|integer|min:0|max:9999',
            'is_recommended' => 'nullable|in:0,1',
            'manual_lat' => 'nullable|numeric|between:-90,90',
            'manual_lng' => 'nullable|numeric|between:-180,180',
            'manual_direction_url' => 'nullable|string|max:2000',
            'disable_directions' => 'nullable|in:0,1',
            'admin_note' => 'nullable|string|max:2000',
        ]);

        $userId = auth()->id();
        $payload = [
            'property_id' => $data['property_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'google_place_id' => $data['google_place_id'] ?? null,
            'normalized_name' => !empty($data['display_name'] ?? $data['name'])
                ? $overrideService->normalizeName((string) ($data['display_name'] ?? $data['name']))
                : null,
            'action' => $data['action'],
            'display_name' => $data['display_name'] ?? $data['name'] ?? null,
            'display_distance_text' => $data['display_distance_text'] ?? null,
            'display_order' => $data['display_order'] ?? null,
            'is_recommended' => $request->boolean('is_recommended'),
            'manual_lat' => $data['manual_lat'] ?? null,
            'manual_lng' => $data['manual_lng'] ?? null,
            'manual_direction_url' => $data['manual_direction_url'] ?? null,
            'disable_directions' => $request->boolean('disable_directions'),
            'admin_note' => $data['admin_note'] ?? null,
            'updated_by' => $userId,
        ];

        if (!empty($data['id'])) {
            $override = NearbyPlaceOverride::query()->findOrFail((int) $data['id']);
            $override->update($payload);

            return ResponseService::successResponse('Override updated');
        }

        $payload['created_by'] = $userId;
        $override = NearbyPlaceOverride::create($payload);

        return ResponseService::successResponse('Override created');
    }

    public function destroyOverride(NearbyPlaceOverride $override)
    {
        if (!has_permissions('update', 'web_settings')) {
            return ResponseService::errorResponse(trans(PERMISSION_ERROR_MSG));
        }

        $override->delete();

        return ResponseService::successResponse('Override deleted');
    }

    /**
     * @return array<string, int>
     */
    protected function buildDashboardStats(): array
    {
        $nearbyRows = (int) DB::table('nearby_place_cache')->count();
        $propertiesWithCache = (int) DB::table('nearby_place_cache')
            ->selectRaw('COUNT(DISTINCT property_id) as aggregate')
            ->value('aggregate');
        $expiredNearby = (int) DB::table('nearby_place_cache')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->count();

        $travelRows = 0;
        if (Schema::hasTable('travel_time_cache')) {
            $travelRows = (int) DB::table('travel_time_cache')->count();
        }

        return [
            'nearby_cache_rows' => $nearbyRows,
            'travel_cache_rows' => $travelRows,
            'properties_with_cache' => $propertiesWithCache,
            'expired_nearby_rows' => $expiredNearby,
            'active_categories' => (int) NearbyCategory::query()->count(),
            'archived_categories' => (int) NearbyCategory::onlyTrashed()->count(),
        ];
    }

    /**
     * @return array<int, array{id:int,title:string,row_count:int}>
     */
    protected function loadCachedProperties(): array
    {
        if (!Schema::hasTable('nearby_place_cache')) {
            return [];
        }

        $rows = DB::table('nearby_place_cache')
            ->select('property_id', DB::raw('COUNT(*) as row_count'))
            ->groupBy('property_id')
            ->orderByDesc('row_count')
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $propertyId = (int) $row->property_id;
            $property = Property::query()->find($propertyId);
            $items[] = [
                'id' => $propertyId,
                'title' => $property?->title ?: ('Property #' . $propertyId),
                'row_count' => (int) $row->row_count,
            ];
        }

        return $items;
    }

    /**
     * @return array<int, int>
     */
    protected function cachedPropertyIds(): array
    {
        if (!Schema::hasTable('nearby_place_cache')) {
            return [];
        }

        return DB::table('nearby_place_cache')
            ->distinct()
            ->orderBy('property_id')
            ->pluck('property_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
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
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'min_reviews' => 'nullable|integer|min:0|max:10000',
            'allow_unrated' => 'nullable|in:0,1',
            'hide_generic_places' => 'nullable|in:0,1',
            'hide_suspicious_same_location' => 'nullable|in:0,1',
            'max_distance_m' => 'nullable|integer|min:100|max:50000',
            'max_results_after_filter' => 'nullable|integer|min:1|max:20',
            'cache_ttl_value' => 'nullable|integer|min:1|max:8760',
            'cache_ttl_unit' => 'nullable|in:hours,days',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|in:0,1',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeCategoryQualityFields(array $data, Request $request): array
    {
        $data['min_rating'] = isset($data['min_rating']) ? (float) $data['min_rating'] : 3.5;
        $data['min_reviews'] = isset($data['min_reviews']) ? (int) $data['min_reviews'] : 3;
        $data['allow_unrated'] = $request->boolean('allow_unrated');
        $data['hide_generic_places'] = $request->boolean('hide_generic_places', true);
        $data['hide_suspicious_same_location'] = $request->boolean('hide_suspicious_same_location', true);
        $data['max_distance_m'] = isset($data['max_distance_m']) && $data['max_distance_m'] !== ''
            ? (int) $data['max_distance_m']
            : null;
        $data['max_results_after_filter'] = isset($data['max_results_after_filter']) && $data['max_results_after_filter'] !== ''
            ? (int) $data['max_results_after_filter']
            : null;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeCategoryCacheTtlFields(array $data, Request $request): array
    {
        $value = $request->input('cache_ttl_value');

        if ($value === null || $value === '') {
            $data['cache_ttl_hours'] = null;

            return $data;
        }

        $unit = (string) $request->input('cache_ttl_unit', 'days');
        $hours = $unit === 'days'
            ? (int) $value * 24
            : (int) $value;

        $data['cache_ttl_hours'] = max(1, min(8760, $hours));

        return $data;
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
