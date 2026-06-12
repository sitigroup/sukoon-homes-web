<?php

namespace App\Plugins\AreaListing\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Plugins\AreaListing\Models\Area;
use App\Plugins\AreaListing\Models\City;
use App\Plugins\AreaListing\Models\State;
use App\Plugins\AreaListing\Models\SubArea;
use App\Plugins\AreaListing\Services\AreaListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AreaListingApiController extends Controller
{
    public function permissions(Request $request)
    {
        $actor = AreaListingService::resolveAreaListingActor();
        if ($actor instanceof Customer && $actor->id) {
            $actor = Customer::query()->find($actor->id) ?? $actor;
        }
        $allowed = AreaListingService::userCanAutoCreateAreas($actor);

        return response()->json([
            'error' => false,
            'message' => 'Area listing permissions fetched successfully',
            'data' => [
                'can_manage_area_listing' => $allowed,
                'can_auto_create_areas' => $allowed,
            ],
        ]);
    }

    public function states(Request $request)
    {
        $cacheKey = 'api:area-states:' . md5(json_encode($request->only(['country'])));
        $data = Cache::remember($cacheKey, 3600, fn () => AreaListingService::states($request->only(['country'])));

        return response()->json(['error' => false, 'message' => 'States fetched successfully', 'data' => $data]);
    }

    public function cities(Request $request)
    {
        $cacheKey = 'api:area-cities:' . md5(json_encode($request->only(['state_id', 'state', 'country'])));
        $data = Cache::remember($cacheKey, 3600, fn () => AreaListingService::cities($request->only(['state_id', 'state', 'country'])));

        return response()->json(['error' => false, 'message' => 'Cities fetched successfully', 'data' => $data]);
    }

    public function areas(Request $request)
    {
        if ($request->filled('per_page') && ! $request->filled('city_id')) {
            return response()->json([
                'error' => true,
                'message' => 'city_id is required when per_page is used',
            ], 422);
        }

        $filters = $request->only(['state_id', 'city_id', 'city', 'state', 'country', 'per_page', 'page']);

        try {
            $result = AreaListingService::areas($filters);
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'error' => true,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $mapper = function ($area) {
            return [
                'id' => $area->id,
                'name' => $area->name,
                'slug' => $area->slug,
                'city' => $area->city_name ?: $area->city,
                'state' => $area->state,
                'country' => $area->country,
                'seo_title' => $area->seo_title,
                'seo_description' => $area->seo_description,
                'sub_areas' => $area->subAreas->map(fn ($subArea) => [
                    'id' => $subArea->id,
                    'area_id' => $subArea->area_id,
                    'name' => $subArea->name,
                    'slug' => $subArea->slug,
                    'seo_title' => $subArea->seo_title,
                    'seo_description' => $subArea->seo_description,
                ])->values(),
            ];
        };

        if ($result instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            return response()->json([
                'error' => false,
                'message' => 'Areas fetched successfully',
                'data' => $result->getCollection()->map($mapper)->values(),
                'meta' => [
                    'total' => $result->total(),
                    'current_page' => $result->currentPage(),
                    'last_page' => $result->lastPage(),
                    'per_page' => $result->perPage(),
                ],
            ]);
        }

        return response()->json([
            'error' => false,
            'message' => 'Areas fetched successfully',
            'data' => $result->map($mapper)->values(),
        ]);
    }

    public function subAreas(Request $request)
    {
        $validator = Validator::make($request->all(), ['area_id' => 'required|exists:area_listing_areas,id']);
        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 422);
        }
        $subAreas = SubArea::where('area_id', $request->area_id)
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn ($subArea) => [
                'id' => $subArea->id,
                'area_id' => $subArea->area_id,
                'name' => $subArea->name,
                'slug' => $subArea->slug,
                'seo_title' => $subArea->seo_title,
                'seo_description' => $subArea->seo_description,
            ]);

        return response()->json(['error' => false, 'message' => 'Sub areas fetched successfully', 'data' => $subAreas]);
    }

    public function storeState(Request $request)
    {
        $validator = Validator::make($request->all(), ['name' => 'required|string|max:255', 'country' => 'nullable|string|max:255']);
        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 422);
        }
        $name = Str::title(Str::lower(trim($request->name)));
        $state = State::updateOrCreate(['country' => $request->country ?: 'India', 'slug' => Str::slug($name)], ['name' => $name, 'status' => true]);
        return response()->json(['error' => false, 'message' => 'State saved successfully', 'data' => $state]);
    }

    public function storeCity(Request $request)
    {
        $validator = Validator::make($request->all(), ['name' => 'required|string|max:255', 'state_id' => 'nullable|exists:area_listing_states,id']);
        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 422);
        }
        $name = Str::title(Str::lower(trim($request->name)));
        $state = $request->state_id ? State::find($request->state_id) : null;
        $city = City::updateOrCreate(
            ['state_id' => $state?->id, 'slug' => Str::slug($name)],
            [
                'name' => $name,
                'normalized_name' => $this->normalizedName($name),
                'state' => $state?->name,
                'country' => $state?->country ?: 'India',
                'status' => true,
            ]
        );
        return response()->json(['error' => false, 'message' => 'City saved successfully', 'data' => $city]);
    }

    public function storeArea(Request $request)
    {
        if (! AreaListingService::canAutoCreateAreasOnSave()) {
            return response()->json([
                'error' => true,
                'message' => 'New areas must be submitted for admin approval. Use suggest-area instead.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'city_id' => 'nullable|exists:area_listing_cities,id',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 422);
        }
        $name = Str::title(Str::lower(trim($request->name)));
        $city = $request->city_id ? City::find($request->city_id) : null;
        if (! $city && $request->filled('city')) {
            $cityQuery = City::query()->where('name', Str::title(Str::lower(trim($request->city))));
            if ($request->filled('state')) {
                $cityQuery->where('state', Str::title(Str::lower(trim($request->state))));
            }
            $city = $cityQuery->first();
        }
        $country = $city?->country ?: ($request->country ?: 'India');
        $cityName = $city?->name ?: Str::title(Str::lower(trim((string) $request->city)));
        $stateName = $city?->state ?: Str::title(Str::lower(trim((string) $request->state)));
        $areaKey = ['normalized_name' => $this->normalizedName($name)];
        if ($city?->id) {
            $areaKey['city_id'] = $city->id;
        } else {
            $areaKey['city'] = $cityName;
            $areaKey['state'] = $stateName;
            $areaKey['country'] = $country;
        }
        $area = Area::updateOrCreate(
            $areaKey,
            [
                'state_id' => $city?->state_id,
                'city_id' => $city?->id,
                'city_name' => $cityName,
                'city' => $cityName,
                'state' => $stateName,
                'country' => $country,
                'name' => $name,
                'slug' => Str::slug($name),
                'center_lat' => $request->center_lat,
                'center_lng' => $request->center_lng,
                'radius_meters' => $request->radius_meters,
                'status' => true,
                'workflow_status' => 'active',
            ]
        );
        return response()->json(['error' => false, 'message' => 'Area saved successfully', 'data' => $area->load('subAreas')]);
    }

    public function storeSubArea(Request $request)
    {
        if (! AreaListingService::canAutoCreateAreasOnSave()) {
            return response()->json([
                'error' => true,
                'message' => 'New sub areas must be submitted for admin approval. Use suggest-sub-area instead.',
            ], 403);
        }

        $validator = Validator::make($request->all(), ['area_id' => 'required|exists:area_listing_areas,id', 'name' => 'required|string|max:255']);
        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 422);
        }
        $name = Str::title(Str::lower(trim($request->name)));
        $subArea = SubArea::updateOrCreate(
            ['area_id' => $request->area_id, 'normalized_name' => $this->normalizedName($name)],
            ['name' => $name, 'slug' => Str::slug($name), 'center_lat' => $request->center_lat, 'center_lng' => $request->center_lng, 'radius_meters' => $request->radius_meters, 'status' => true, 'workflow_status' => 'active']
        );
        return response()->json(['error' => false, 'message' => 'Sub area saved successfully', 'data' => $subArea]);
    }

    public function suggestArea(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'sub_area_name' => 'nullable|string|max:255',
            'area_id' => 'nullable|exists:area_listing_areas,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 422);
        }

        $groupToken = null;
        $subAreaName = trim((string) $request->input('sub_area_name', ''));
        if ($subAreaName !== '') {
            $groupToken = (string) \Illuminate\Support\Str::uuid();
        }

        AreaListingService::storePendingSuggestion('area', [
            'name' => $request->name,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country ?? 'India',
            'group_token' => $groupToken,
        ]);

        if ($subAreaName !== '') {
            AreaListingService::storePendingSuggestion('sub_area', [
                'name' => $subAreaName,
                'area_id' => $request->input('area_id'),
                'group_token' => $groupToken,
                'pair_with_area_suggestion' => true,
            ]);
        }

        return response()->json(['error' => false, 'message' => 'Area suggestion submitted for admin approval']);
    }

    public function suggestSubArea(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'area_id' => 'required|exists:area_listing_areas,id',
            'name' => 'required|string|max:255',
            'group_token' => 'nullable|uuid',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 422);
        }

        AreaListingService::storePendingSuggestion('sub_area', [
            'name' => $request->name,
            'area_id' => $request->area_id,
            'group_token' => $request->input('group_token'),
        ]);

        return response()->json(['error' => false, 'message' => 'Sub area suggestion submitted for admin approval']);
    }

    public function resolveCoordinates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'city_id' => 'nullable|integer',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'location_components' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $locationComponents = json_decode((string) $request->input('location_components', '[]'), true);
        if (! is_array($locationComponents)) {
            $locationComponents = [];
        }

        $data = AreaListingService::resolveNearestFromCoordinates(
            (float) $request->latitude,
            (float) $request->longitude,
            $request->filled('city_id') ? (int) $request->city_id : null,
            (string) $request->input('city', ''),
            (string) $request->input('state', ''),
            (string) $request->input('country', 'India'),
            $locationComponents
        );

        return response()->json([
            'error' => false,
            'message' => $data ? __('Location resolved') : __('No matching area found'),
            'data' => $data,
        ]);
    }

    private function normalizedName($value): string
    {
        return Str::of((string) $value)->lower()->squish()->value();
    }
}
