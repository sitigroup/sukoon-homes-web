<?php

namespace App\Plugins\AreaListing\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Plugins\AreaListing\Models\Area;
use App\Plugins\AreaListing\Models\City;
use App\Plugins\AreaListing\Models\State;
use App\Plugins\AreaListing\Models\SubArea;
use App\Plugins\AreaListing\Services\AreaListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AreaListingApiController extends Controller
{
    public function states(Request $request)
    {
        return response()->json(['error' => false, 'message' => 'States fetched successfully', 'data' => AreaListingService::states($request->only(['country']))]);
    }

    public function cities(Request $request)
    {
        return response()->json(['error' => false, 'message' => 'Cities fetched successfully', 'data' => AreaListingService::cities($request->only(['state_id', 'state', 'country']))]);
    }

    public function areas(Request $request)
    {
        $areas = AreaListingService::areas($request->only(['state_id', 'city_id', 'city', 'state', 'country']))->map(function ($area) {
            return [
                'id' => $area->id,
                'name' => $area->name,
                'slug' => $area->slug,
                'city' => $area->city_name ?: $area->city,
                'state' => $area->state,
                'country' => $area->country,
                'sub_areas' => $area->subAreas->map(fn ($subArea) => [
                    'id' => $subArea->id,
                    'area_id' => $subArea->area_id,
                    'name' => $subArea->name,
                    'slug' => $subArea->slug,
                ])->values(),
            ];
        });

        return response()->json(['error' => false, 'message' => 'Areas fetched successfully', 'data' => $areas]);
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
        $city = City::updateOrCreate(['state_id' => $state?->id, 'slug' => Str::slug($name)], ['name' => $name, 'state' => $state?->name, 'country' => $state?->country ?: 'India', 'status' => true]);
        return response()->json(['error' => false, 'message' => 'City saved successfully', 'data' => $city]);
    }

    public function storeArea(Request $request)
    {
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
        $country = $city?->country ?: ($request->country ?: 'India');
        $area = Area::updateOrCreate(
            [
                'city' => $city?->name ?: $request->city,
                'state' => $city?->state ?: $request->state,
                'country' => $country,
                'normalized_name' => $this->normalizedName($name),
            ],
            [
                'state_id' => $city?->state_id,
                'city_id' => $city?->id,
                'city_name' => $city?->name ?: $request->city,
                'city' => $city?->name ?: $request->city,
                'state' => $city?->state ?: $request->state,
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
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 422);
        }

        return response()->json(['error' => false, 'message' => 'Area suggestion submitted for admin approval', 'data' => $this->storeSuggestion('area', $request)]);
    }

    public function suggestSubArea(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'area_id' => 'required|exists:area_listing_areas,id',
            'name' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()->first()], 422);
        }

        return response()->json(['error' => false, 'message' => 'Sub area suggestion submitted for admin approval', 'data' => $this->storeSuggestion('sub_area', $request)]);
    }

    private function storeSuggestion(string $type, Request $request)
    {
        $name = Str::title(Str::lower(trim($request->name)));

        DB::table('area_listing_suggestions')->updateOrInsert(
            [
                'type' => $type,
                'area_id' => $request->input('area_id'),
                'normalized_name' => $this->normalizedName($name),
                'status' => 'pending',
            ],
            [
                'name' => $name,
                'slug' => Str::slug($name),
                'city' => $request->input('city'),
                'state' => $request->input('state'),
                'country' => $request->input('country', 'India'),
                'suggested_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return DB::table('area_listing_suggestions')
            ->where('type', $type)
            ->where('area_id', $request->input('area_id'))
            ->where('normalized_name', $this->normalizedName($name))
            ->where('status', 'pending')
            ->first();
    }

    private function normalizedName($value): string
    {
        return Str::of((string) $value)->lower()->squish()->value();
    }
}
