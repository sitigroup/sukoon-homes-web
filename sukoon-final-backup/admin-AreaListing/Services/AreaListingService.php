<?php

namespace App\Plugins\AreaListing\Services;

use App\Models\Property;
use App\Models\Projects;
use App\Plugins\AreaListing\Models\Area;
use App\Plugins\AreaListing\Models\City;
use App\Plugins\AreaListing\Models\State;
use App\Plugins\AreaListing\Models\SubArea;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AreaListingService
{
    public static function canAutoCreateAreasOnSave(): bool
    {
        if (request()->boolean('area_listing_admin_save')) {
            return true;
        }

        return self::userCanAutoCreateAreas(auth()->user());
    }

    /**
     * Admin users and agents with can_manage_area_listing may create areas like admin.
     * Regular agents and end-users must use the suggestion / approval workflow.
     */
    public static function userCanAutoCreateAreas($user = null): bool
    {
        if (! $user) {
            return false;
        }

        if ($user instanceof \App\Models\User) {
            return true;
        }

        if (method_exists($user, 'getTable') && $user->getTable() === 'customers') {
            return (bool) ($user->can_manage_area_listing ?? false);
        }

        return false;
    }

    public static function storePendingSuggestion(string $type, array $data): void
    {
        $name = self::clean($data['name'] ?? '');
        if ($name === '') {
            return;
        }

        $normalized = self::normalized($name);
        $areaId = $type === 'sub_area' ? ($data['area_id'] ?? null) : null;

        DB::table('area_listing_suggestions')->updateOrInsert(
            [
                'type' => $type,
                'area_id' => $areaId,
                'normalized_name' => $normalized,
                'status' => 'pending',
            ],
            [
                'name' => $name,
                'slug' => Str::slug($name),
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'country' => $data['country'] ?? 'India',
                'suggested_by' => auth()->id(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    private static function unwrapNewTagId($value): array
    {
        $raw = self::pickScalar($value);
        if ($raw === null || $raw === '') {
            return [null, ''];
        }

        $string = (string) $raw;
        if (str_starts_with($string, '__new__:')) {
            return [null, self::clean(substr($string, 8))];
        }

        return [$string, ''];
    }

    public static function savePropertyLocation($property, Request $request): void
    {
        $merged = self::buildMergedPropertyAreaListingRequest($property, $request);
        self::saveListingLocation('area_listing_property_locations', 'property_id', $property->id, $merged);
    }

    /**
     * Merge nested location[], top-level API fields, saved Property columns, and existing snapshot.
     * Preserves manual area/sub_area IDs when the request omits them (GPS cannot silently drop selection).
     */
    public static function buildMergedPropertyAreaListingRequest(Property $property, Request $request): Request
    {
        $loc = $request->input('location', []);
        $loc = is_array($loc) ? $loc : [];

        $existing = DB::table('area_listing_property_locations')->where('property_id', $property->id)->first();

        $locAreaProvided = array_key_exists('area_id', $loc);
        $topAreaProvided = $request->has('area_id');
        $locSubProvided = array_key_exists('sub_area_id', $loc);
        $topSubProvided = $request->has('sub_area_id');

        $hasIncomingAreaIdPayload = $locAreaProvided || $topAreaProvided;
        $hasIncomingSubAreaIdPayload = $locSubProvided || $topSubProvided;

        $incomingAreaId = null;
        if ($hasIncomingAreaIdPayload) {
            $incomingAreaId = self::pickScalar(
                $locAreaProvided ? ($loc['area_id'] ?? null) : null,
                $topAreaProvided ? $request->input('area_id') : null
            );
        }

        $incomingSubAreaId = null;
        if ($hasIncomingSubAreaIdPayload) {
            $incomingSubAreaId = self::pickScalar(
                $locSubProvided ? ($loc['sub_area_id'] ?? null) : null,
                $topSubProvided ? $request->input('sub_area_id') : null
            );
        }

        [$incomingAreaId, $newAreaFromTag] = self::unwrapNewTagId($incomingAreaId);
        [$incomingSubAreaId, $newSubFromTag] = self::unwrapNewTagId($incomingSubAreaId);

        $areaId = $incomingAreaId;
        $subAreaId = $incomingSubAreaId;

        if ($existing) {
            if (! $hasIncomingAreaIdPayload && ! empty($existing->area_id)) {
                $areaId = $existing->area_id;
            }
            if (! $hasIncomingSubAreaIdPayload && ! empty($existing->sub_area_id)) {
                $areaChanged = $hasIncomingAreaIdPayload
                    && (string) ($incomingAreaId ?? '') !== (string) ($existing->area_id ?? '');
                if (! $areaChanged) {
                    $subAreaId = $existing->sub_area_id;
                    if ($areaId && ! self::subAreaBelongsToArea($subAreaId, $areaId)) {
                        $subAreaId = null;
                    }
                }
            }
        }

        $hadDetectedAreaInput = array_key_exists('detected_area_name', $loc)
            || $request->has('detected_area_name');

        $hadDetectedSubInput = array_key_exists('detected_sub_area_name', $loc)
            || $request->has('detected_sub_area_name');

        if ($hadDetectedAreaInput) {
            $detectedArea = self::clean(self::pickString(
                $loc['detected_area_name'] ?? null,
                $request->input('detected_area_name'),
                $request->input('area_name')
            ));
        } elseif ($existing && ($existing->detected_area_name ?? '') !== '') {
            $detectedArea = self::clean($existing->detected_area_name);
        } else {
            $detectedArea = '';
        }

        if ($hadDetectedSubInput) {
            $detectedSub = self::clean(self::pickString(
                $loc['detected_sub_area_name'] ?? null,
                $request->input('detected_sub_area_name'),
                $request->input('sub_area_name')
            ));
        } elseif ($existing && ($existing->detected_sub_area_name ?? '') !== '') {
            $detectedSub = self::clean($existing->detected_sub_area_name);
        } else {
            $detectedSub = '';
        }

        if ($newAreaFromTag !== '') {
            $detectedArea = $newAreaFromTag;
        }
        if ($newSubFromTag !== '') {
            $detectedSub = $newSubFromTag;
        }

        if ($hasIncomingSubAreaIdPayload && $incomingSubAreaId === null) {
            $detectedSub = '';
        }

        [$areaId, $subAreaId, $detectedSub] = self::dropMismatchedSubAreaSelection(
            $areaId,
            $subAreaId,
            $detectedSub,
            $existing,
            $hasIncomingAreaIdPayload,
            $incomingAreaId
        );

        $cityName = self::clean(self::pickString(
            $loc['city'] ?? null,
            $request->input('city'),
            $property->city ?? null
        ));

        $stateName = self::clean(self::pickString(
            $loc['state'] ?? null,
            $request->input('state'),
            $property->state ?? null
        ));

        $country = self::clean(self::pickString(
            $loc['country'] ?? null,
            $request->input('country'),
            $property->country ?? null,
            'India'
        ));

        $cityId = self::pickScalar($loc['city_id'] ?? null, $request->input('city_id'));
        $stateId = self::pickScalar($loc['state_id'] ?? null, $request->input('state_id'));
        $cityId = self::resolveCityIdForSnapshot($cityId, $cityName, $stateName, $country);
        if ($cityId && empty($stateId)) {
            $stateId = City::find($cityId)?->state_id;
        }

        $latitude = self::pickScalar($loc['latitude'] ?? null, $request->input('latitude'), $property->latitude ?? null);
        $longitude = self::pickScalar($loc['longitude'] ?? null, $request->input('longitude'), $property->longitude ?? null);

        $hadSourceInput = array_key_exists('source', $loc) || $request->has('area_listing_source');

        $source = $hadSourceInput
            ? self::pickString($loc['source'] ?? '', $request->input('area_listing_source') ?: '')
            : self::pickString($existing->location_source ?? null, $existing->source ?? null, 'manual');

        if ($source === '') {
            $source = 'manual';
        }

        $address = self::pickString(
            array_key_exists('address', $loc) ? ($loc['address'] ?? null) : null,
            $request->input('address'),
            $request->input('location')
        );

        $manualAddress = self::pickString(
            array_key_exists('manual_address', $loc) ? ($loc['manual_address'] ?? null) : null,
            $request->input('manual_address'),
            $request->input('client_address'),
            $request->input('customer_address')
        );

        $payload = [
            'state' => $stateName,
            'city' => $cityName,
            'country' => $country,
            'city_id' => $cityId,
            'state_id' => $stateId,
            'area_id' => $areaId,
            'sub_area_id' => $subAreaId,
            'detected_area_name' => $detectedArea,
            'detected_sub_area_name' => $detectedSub,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'address' => $address !== '' ? $address : null,
            'manual_address' => $manualAddress !== '' ? $manualAddress : null,
            'area_listing_source' => $source,
            'location_is_verified' => $request->has('location_is_verified')
                ? $request->input('location_is_verified')
                : ($loc['location_is_verified'] ?? null),
        ];

        return Request::create('/', 'POST', $payload);
    }

    /**
     * Merge nested location[], top-level API fields, saved Project columns, and existing snapshot.
     * Preserves manual area/sub_area IDs when the request omits them (GPS cannot silently drop selection).
     */
    public static function buildMergedProjectAreaListingRequest(Projects $project, Request $request): Request
    {
        $loc = $request->input('location', []);
        $loc = is_array($loc) ? $loc : [];

        $existing = DB::table('area_listing_project_locations')->where('project_id', $project->id)->first();

        $locAreaProvided = array_key_exists('area_id', $loc);
        $topAreaProvided = $request->has('area_id');
        $locSubProvided = array_key_exists('sub_area_id', $loc);
        $topSubProvided = $request->has('sub_area_id');

        $hasIncomingAreaIdPayload = $locAreaProvided || $topAreaProvided;
        $hasIncomingSubAreaIdPayload = $locSubProvided || $topSubProvided;

        $incomingAreaId = null;
        if ($hasIncomingAreaIdPayload) {
            $incomingAreaId = self::pickScalar(
                $locAreaProvided ? ($loc['area_id'] ?? null) : null,
                $topAreaProvided ? $request->input('area_id') : null
            );
        }

        $incomingSubAreaId = null;
        if ($hasIncomingSubAreaIdPayload) {
            $incomingSubAreaId = self::pickScalar(
                $locSubProvided ? ($loc['sub_area_id'] ?? null) : null,
                $topSubProvided ? $request->input('sub_area_id') : null
            );
        }

        [$incomingAreaId, $newAreaFromTag] = self::unwrapNewTagId($incomingAreaId);
        [$incomingSubAreaId, $newSubFromTag] = self::unwrapNewTagId($incomingSubAreaId);

        $areaId = $incomingAreaId;
        $subAreaId = $incomingSubAreaId;

        if ($existing) {
            if (! $hasIncomingAreaIdPayload && ! empty($existing->area_id)) {
                $areaId = $existing->area_id;
            }
            if (! $hasIncomingSubAreaIdPayload && ! empty($existing->sub_area_id)) {
                $areaChanged = $hasIncomingAreaIdPayload
                    && (string) ($incomingAreaId ?? '') !== (string) ($existing->area_id ?? '');
                if (! $areaChanged) {
                    $subAreaId = $existing->sub_area_id;
                    if ($areaId && ! self::subAreaBelongsToArea($subAreaId, $areaId)) {
                        $subAreaId = null;
                    }
                }
            }
        }

        $hadDetectedAreaInput = array_key_exists('detected_area_name', $loc)
            || $request->has('detected_area_name');

        $hadDetectedSubInput = array_key_exists('detected_sub_area_name', $loc)
            || $request->has('detected_sub_area_name');

        if ($hadDetectedAreaInput) {
            $detectedArea = self::clean(self::pickString(
                $loc['detected_area_name'] ?? null,
                $request->input('detected_area_name'),
                $request->input('area_name')
            ));
        } elseif ($existing && ($existing->detected_area_name ?? '') !== '') {
            $detectedArea = self::clean($existing->detected_area_name);
        } else {
            $detectedArea = '';
        }

        if ($hadDetectedSubInput) {
            $detectedSub = self::clean(self::pickString(
                $loc['detected_sub_area_name'] ?? null,
                $request->input('detected_sub_area_name'),
                $request->input('sub_area_name')
            ));
        } elseif ($existing && ($existing->detected_sub_area_name ?? '') !== '') {
            $detectedSub = self::clean($existing->detected_sub_area_name);
        } else {
            $detectedSub = '';
        }

        if ($newAreaFromTag !== '') {
            $detectedArea = $newAreaFromTag;
        }
        if ($newSubFromTag !== '') {
            $detectedSub = $newSubFromTag;
        }

        if ($hasIncomingSubAreaIdPayload && $incomingSubAreaId === null) {
            $detectedSub = '';
        }

        [$areaId, $subAreaId, $detectedSub] = self::dropMismatchedSubAreaSelection(
            $areaId,
            $subAreaId,
            $detectedSub,
            $existing,
            $hasIncomingAreaIdPayload,
            $incomingAreaId
        );

        $cityName = self::clean(self::pickString(
            $loc['city'] ?? null,
            $request->input('city'),
            $project->city ?? null
        ));

        $stateName = self::clean(self::pickString(
            $loc['state'] ?? null,
            $request->input('state'),
            $project->state ?? null
        ));

        $country = self::clean(self::pickString(
            $loc['country'] ?? null,
            $request->input('country'),
            $project->country ?? null,
            'India'
        ));

        $cityId = self::pickScalar($loc['city_id'] ?? null, $request->input('city_id'));
        $stateId = self::pickScalar($loc['state_id'] ?? null, $request->input('state_id'));
        $cityId = self::resolveCityIdForSnapshot($cityId, $cityName, $stateName, $country);
        if ($cityId && empty($stateId)) {
            $stateId = City::find($cityId)?->state_id;
        }

        $latitude = self::pickScalar($loc['latitude'] ?? null, $request->input('latitude'), $project->latitude ?? null);
        $longitude = self::pickScalar($loc['longitude'] ?? null, $request->input('longitude'), $project->longitude ?? null);

        $hadSourceInput = array_key_exists('source', $loc) || $request->has('area_listing_source');

        $source = $hadSourceInput
            ? self::pickString($loc['source'] ?? '', $request->input('area_listing_source') ?: '')
            : self::pickString($existing->location_source ?? null, $existing->source ?? null, 'manual');

        if ($source === '') {
            $source = 'manual';
        }

        $topLocation = $request->input('location');
        $topLocationAddress = is_array($topLocation) ? null : $topLocation;

        $address = self::pickString(
            array_key_exists('address', $loc) ? ($loc['address'] ?? null) : null,
            $request->input('address'),
            $topLocationAddress
        );

        $manualAddress = self::pickString(
            array_key_exists('manual_address', $loc) ? ($loc['manual_address'] ?? null) : null,
            $request->input('manual_address'),
            $request->input('client_address'),
            $request->input('customer_address')
        );

        $payload = [
            'state' => $stateName,
            'city' => $cityName,
            'country' => $country,
            'city_id' => $cityId,
            'state_id' => $stateId,
            'area_id' => $areaId,
            'sub_area_id' => $subAreaId,
            'detected_area_name' => $detectedArea,
            'detected_sub_area_name' => $detectedSub,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'address' => $address !== '' ? $address : null,
            'manual_address' => $manualAddress !== '' ? $manualAddress : null,
            'area_listing_source' => $source,
            'location_is_verified' => $request->has('location_is_verified')
                ? $request->input('location_is_verified')
                : ($loc['location_is_verified'] ?? null),
        ];

        return Request::create('/', 'POST', $payload);
    }

    public static function saveProjectLocation($project, Request $request): void
    {
        $merged = self::buildMergedProjectAreaListingRequest($project, $request);
        self::saveListingLocation('area_listing_project_locations', 'project_id', $project->id, $merged);
    }

    public static function applyPropertyFilters(Builder $query, Request $request, array $filters = []): Builder
    {
        return self::applyListingFilters($query, 'propertys.id', 'area_listing_property_locations', 'property_id', $request, $filters);
    }

    public static function applyProjectFilters(Builder $query, Request $request, array $filters = []): Builder
    {
        return self::applyListingFilters($query, 'projects.id', 'area_listing_project_locations', 'project_id', $request, $filters);
    }

    public static function states(array $filters = [])
    {
        return State::query()
            ->when(! empty($filters['country']), fn ($q) => $q->where('country', 'like', '%' . $filters['country'] . '%'))
            ->where('status', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public static function cities(array $filters = [])
    {
        return City::query()
            ->when(! empty($filters['state_id']), fn ($q) => $q->where('state_id', $filters['state_id']))
            ->when(! empty($filters['state']), fn ($q) => $q->where('state', 'like', '%' . $filters['state'] . '%'))
            ->when(! empty($filters['country']), fn ($q) => $q->where('country', 'like', '%' . $filters['country'] . '%'))
            ->where('status', 1)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public static function areas(array $filters = [])
    {
        $query = Area::query()
            ->with(['subAreas' => fn ($q) => $q->active()->orderBy('name')])
            ->when(! empty($filters['state_id']), fn ($q) => $q->where('state_id', $filters['state_id']));

        self::applyAreaCityFilter($query, $filters);

        if (! empty($filters['per_page'])) {
            if (empty($filters['city_id'])) {
                throw new \InvalidArgumentException('city_id is required when per_page is used');
            }

            $perPage = max(1, min(100, (int) $filters['per_page']));
            $page = max(1, (int) ($filters['page'] ?? 1));

            return $query->active()->orderBy('name')->paginate($perPage, ['*'], 'page', $page);
        }

        return $query->active()->orderBy('name')->get();
    }

    public static function decoratePropertyResponse(array $row, $property, ?int $currentUserId): array
    {
        $location = self::listingLocation('area_listing_property_locations', 'property_id', $property->id);
        $row = self::attachAreaPayload($row, $location, $property->city, $property->state, $property->country);
        $canViewExact = self::canViewExactLocation($property->added_by ?? null, $currentUserId);
        $row['can_view_exact_location'] = $canViewExact;
        if (! $canViewExact) {
            $row['address'] = self::publicLocationLabel($row['area_listing'], $property->city, $property->state, $property->country);
            $row['client_address'] = null;
            $row['latitude'] = null;
            $row['longitude'] = null;
            $row['area_listing']['full_address'] = null;
            $row['area_listing']['manual_address'] = null;
            $row['area_listing']['latitude'] = null;
            $row['area_listing']['longitude'] = null;
        }
        return $row;
    }

    public static function decoratePropertyModel($property, ?int $currentUserId)
    {
        if (! $property) {
            return $property;
        }
        $location = self::listingLocation('area_listing_property_locations', 'property_id', $property->id);
        $areaPayload = self::areaPayload($location, $property->city, $property->state, $property->country);
        $property->area_listing = $areaPayload;
        $canViewExact = self::canViewExactLocation($property->added_by ?? null, $currentUserId);
        $property->can_view_exact_location = $canViewExact;
        if (! $canViewExact) {
            $property->address = self::publicLocationLabel($areaPayload, $property->city, $property->state, $property->country);
            $property->client_address = null;
            $property->latitude = null;
            $property->longitude = null;
            $areaPayload['full_address'] = null;
            $areaPayload['manual_address'] = null;
            $areaPayload['latitude'] = null;
            $areaPayload['longitude'] = null;
            $property->area_listing = $areaPayload;
        }
        return $property;
    }

    public static function decorateProjectModel($project, ?int $currentUserId)
    {
        if (! $project) {
            return $project;
        }
        $location = self::listingLocation('area_listing_project_locations', 'project_id', $project->id);
        $areaPayload = self::areaPayload($location, $project->city, $project->state, $project->country);
        $project->area_listing = $areaPayload;
        $canViewExact = self::canViewExactLocation($project->added_by ?? null, $currentUserId);
        $project->can_view_exact_location = $canViewExact;
        if (! $canViewExact) {
            $project->location = self::publicLocationLabel($areaPayload, $project->city, $project->state, $project->country);
            $project->latitude = null;
            $project->longitude = null;
            $areaPayload['full_address'] = null;
            $areaPayload['manual_address'] = null;
            $areaPayload['latitude'] = null;
            $areaPayload['longitude'] = null;
            $project->area_listing = $areaPayload;
            if (isset($project->customer) && is_object($project->customer)) {
                $project->customer->address = null;
            }
        }
        return $project;
    }

    private static function saveListingLocation(string $table, string $foreignKey, int $listingId, Request $request): void
    {
        $stateName = self::clean($request->input('state'));
        $cityName = self::clean($request->input('city'));
        $country = self::clean($request->input('country') ?: 'India');
        $areaId = $request->input('area_id');
        $subAreaId = $request->input('sub_area_id');
        $detectedArea = self::clean($request->input('detected_area_name') ?: $request->input('area_name'));
        $detectedSubArea = self::clean($request->input('detected_sub_area_name') ?: $request->input('sub_area_name'));

        // Admin/API forms always send sub_area_id; empty means user left sub-area unset — do not infer from stale detected_* hidden fields.
        if ($request->has('sub_area_id') && ! self::pickScalar($subAreaId)) {
            $subAreaId = null;
            $detectedSubArea = '';
        }

        $selectedArea = ! empty($areaId) ? Area::find($areaId) : null;
        $selectedSubArea = ! empty($subAreaId) ? SubArea::find($subAreaId) : null;
        if ($selectedSubArea && $selectedArea && (int) $selectedSubArea->area_id !== (int) $selectedArea->id) {
            $subAreaId = null;
            $selectedSubArea = null;
            $detectedSubArea = '';
        }
        if (! $selectedArea && $selectedSubArea) {
            $selectedArea = Area::find($selectedSubArea->area_id);
            $areaId = $selectedArea?->id ?: $areaId;
        }

        $selectedCity = $request->input('city_id') ? City::find($request->input('city_id')) : null;
        if ($selectedArea) {
            $cityName = self::clean($selectedArea->city_name ?: $selectedArea->city ?: $cityName);
            $stateName = self::clean($selectedArea->state ?: $stateName);
            $country = self::clean($selectedArea->country ?: $country ?: 'India');
            if ($selectedArea->city_id) {
                $selectedCity = City::find($selectedArea->city_id) ?: $selectedCity;
            }
        } elseif ($selectedCity) {
            $cityName = self::clean($selectedCity->name ?: $cityName);
            $stateName = self::clean($selectedCity->state ?: $stateName);
            $country = self::clean($selectedCity->country ?: $country ?: 'India');
        }

        $state = $stateName !== '' ? State::firstOrCreate(
            ['country' => $country, 'slug' => Str::slug($stateName)],
            ['name' => $stateName, 'status' => true]
        ) : null;

        $city = $cityName !== '' ? City::firstOrCreate(
            ['state_id' => $state?->id, 'slug' => Str::slug($cityName)],
            ['name' => $cityName, 'state' => $stateName, 'country' => $country, 'status' => true]
        ) : null;

        if (empty($areaId) && $detectedArea !== '') {
            if (self::canAutoCreateAreasOnSave()) {
                $area = Area::firstOrCreate(
                    ['city' => $cityName, 'state' => $stateName, 'country' => $country, 'normalized_name' => self::normalized($detectedArea)],
                    ['state_id' => $state?->id, 'city_id' => $city?->id, 'city_name' => $cityName, 'name' => $detectedArea, 'slug' => Str::slug($detectedArea), 'status' => true, 'workflow_status' => 'active']
                );
                $areaId = $area->id;
            } else {
                self::storePendingSuggestion('area', [
                    'name' => $detectedArea,
                    'city' => $cityName,
                    'state' => $stateName,
                    'country' => $country,
                ]);
            }
        }

        if (empty($subAreaId) && $detectedSubArea !== '' && ! empty($areaId)) {
            if (self::canAutoCreateAreasOnSave()) {
                $subArea = SubArea::firstOrCreate(
                    ['area_id' => $areaId, 'normalized_name' => self::normalized($detectedSubArea)],
                    ['name' => $detectedSubArea, 'slug' => Str::slug($detectedSubArea), 'status' => true, 'workflow_status' => 'active']
                );
                $subAreaId = $subArea->id;
            } else {
                self::storePendingSuggestion('sub_area', [
                    'name' => $detectedSubArea,
                    'area_id' => $areaId,
                ]);
            }
        }

        if (empty($areaId) && empty($subAreaId) && $detectedArea === '' && $detectedSubArea === '' && $cityName === '' && $stateName === '') {
            return;
        }

        $area = ! empty($areaId) ? Area::find($areaId) : null;
        $subArea = ! empty($subAreaId) ? SubArea::find($subAreaId) : null;
        $areaName = $area?->name ?: $detectedArea;
        $subAreaName = $subArea?->name ?: $detectedSubArea;
        $displayAddress = self::publicLocationLabel(['sub_area_name' => $subAreaName, 'area_name' => $areaName], $cityName ?: ($area?->city_name ?: $area?->city), $stateName ?: $area?->state, null);

        $existingRow = DB::table($table)->where($foreignKey, $listingId)->first();

        $locationData = [
            'state_id' => $state?->id ?: $area?->state_id,
            'city_id' => $city?->id ?: $area?->city_id,
            'area_id' => $areaId ?: null,
            'sub_area_id' => $subAreaId ?: null,
            'state' => $stateName ?: $area?->state,
            'city' => $cityName ?: ($area?->city_name ?: $area?->city),
            'area_name' => $areaName ?: null,
            'sub_area_name' => $subAreaName ?: null,
            'detected_area_name' => $detectedArea ?: null,
            'detected_sub_area_name' => $detectedSubArea ?: null,
            'full_address' => $request->input('address') ?: $request->input('location'),
            'manual_address' => $request->input('manual_address') ?: $request->input('customer_address') ?: $request->input('client_address'),
            'display_address' => $displayAddress ?: null,
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'location_source' => $request->input('area_listing_source', 'manual'),
            'source' => $request->input('area_listing_source', 'manual'),
            'is_verified' => $request->boolean('location_is_verified', false),
            'updated_by' => auth()->id(),
            'updated_at' => now(),
        ];

        if ($existingRow) {
            DB::table($table)->where($foreignKey, $listingId)->update($locationData);
        } else {
            DB::table($table)->insert(array_merge($locationData, [
                $foreignKey => $listingId,
                'created_at' => now(),
            ]));
        }

        if (
            $table === 'area_listing_property_locations'
            && $existingRow
            && class_exists(\App\Plugins\NearbyPlaces\Services\NearbyPlacesService::class)
        ) {
            $areaChanged = (string) ($existingRow->area_id ?? '') !== (string) ($areaId ?? '');
            $subChanged = (string) ($existingRow->sub_area_id ?? '') !== (string) ($subAreaId ?? '');
            $cityChanged = (string) ($existingRow->city_id ?? '') !== (string) ($city?->id ?? $area?->city_id ?? '');
            $coordsChanged = round((float) ($existingRow->latitude ?? 0), 5) !== round((float) $request->input('latitude'), 5)
                || round((float) ($existingRow->longitude ?? 0), 5) !== round((float) $request->input('longitude'), 5);
            if ($areaChanged || $subChanged || $cityChanged || $coordsChanged) {
                app(\App\Plugins\NearbyPlaces\Services\NearbyPlacesService::class)->clearCacheForProperty($listingId);
            }
        }
    }

    private static function applyListingFilters(Builder $query, string $listingColumn, string $table, string $foreignKey, Request $request, array $filters): Builder
    {
        $stateId = data_get($filters, 'state_id') ?: data_get($filters, 'location.state_id') ?: $request->input('state_id');
        $cityId = data_get($filters, 'city_id') ?: data_get($filters, 'location.city_id') ?: $request->input('city_id');
        $areaId = data_get($filters, 'area_id') ?: data_get($filters, 'location.area_id') ?: $request->input('area_id');
        $subAreaId = data_get($filters, 'sub_area_id') ?: data_get($filters, 'location.sub_area_id') ?: $request->input('sub_area_id');
        $citySlug = $request->input('city');
        $areaSlug = $request->input('area');
        $subAreaSlug = $request->input('sub_area');

        if (empty($areaId) && ! empty($areaSlug)) {
            $areaId = Area::query()
                ->active()
                ->when(! empty($citySlug), fn ($q) => $q->where(function ($inner) use ($citySlug) {
                    $inner->where('city', 'LIKE', str_replace('-', ' ', $citySlug));
                }))
                ->where('slug', $areaSlug)
                ->value('id');
        }

        if (empty($subAreaId) && ! empty($subAreaSlug)) {
            $subAreaQuery = SubArea::query()->active()->where('slug', $subAreaSlug);
            if (! empty($areaId)) {
                $subAreaQuery->where('area_id', $areaId);
            }
            $subAreaId = $subAreaQuery->value('id');
        }

        if (empty($stateId) && empty($cityId) && empty($areaId) && empty($subAreaId)) {
            return $query;
        }

        return $query->whereIn($listingColumn, function ($subQuery) use ($table, $foreignKey, $stateId, $cityId, $areaId, $subAreaId) {
            $subQuery->select($foreignKey)->from($table)
                ->when(! empty($stateId), fn ($q) => $q->where('state_id', $stateId))
                ->when(! empty($cityId), fn ($q) => $q->where('city_id', $cityId))
                ->when(! empty($areaId), fn ($q) => $q->where('area_id', $areaId))
                ->when(! empty($subAreaId), fn ($q) => $q->where('sub_area_id', $subAreaId));
        });
    }

    private static function listingLocation(string $table, string $foreignKey, int $listingId)
    {
        $row = DB::table($table)->where($foreignKey, $listingId)->first();
        if (! $row) {
            return null;
        }
        $row->area = $row->area_id ? Area::find($row->area_id) : null;
        $row->subArea = $row->sub_area_id ? SubArea::find($row->sub_area_id) : null;
        return $row;
    }

    private static function attachAreaPayload(array $row, $location, ?string $city, ?string $state, ?string $country): array
    {
        $row['area_listing'] = self::areaPayload($location, $city, $state, $country);
        return $row;
    }

    private static function areaPayload($location, ?string $city, ?string $state, ?string $country): array
    {
        $area = $location?->area;
        $subArea = $location?->subArea;
        return [
            'state_id' => $location?->state_id ?? $area?->state_id,
            'city_id' => $location?->city_id ?? $area?->city_id,
            'area_id' => $area?->id ?? $location?->area_id,
            'area_name' => $area?->name ?? $location?->area_name ?? $location?->detected_area_name,
            'sub_area_id' => $subArea?->id ?? $location?->sub_area_id,
            'sub_area_name' => $subArea?->name ?? $location?->sub_area_name ?? $location?->detected_sub_area_name,
            'city' => $location?->city ?? ($area?->city_name ?: $area?->city) ?? $city,
            'state' => $location?->state ?? $state,
            'country' => $location?->country ?? $country,
            'display_address' => $location?->display_address,
            'full_address' => $location?->full_address,
            'manual_address' => $location?->manual_address,
            'latitude' => $location?->latitude,
            'longitude' => $location?->longitude,
        ];
    }

    private static function canViewExactLocation($addedBy, ?int $currentUserId): bool
    {
        return ! empty($currentUserId) && ! empty($addedBy) && (int) $addedBy === (int) $currentUserId;
    }

    /**
     * @return array{0: mixed, 1: mixed, 2: string}
     */
    private static function dropMismatchedSubAreaSelection(
        $areaId,
        $subAreaId,
        string $detectedSub,
        $existing,
        bool $hasIncomingAreaIdPayload,
        $incomingAreaId
    ): array {
        if (! $subAreaId) {
            return [$areaId, $subAreaId, $detectedSub];
        }

        $areaChanged = $existing
            && $hasIncomingAreaIdPayload
            && (string) ($incomingAreaId ?? '') !== (string) ($existing->area_id ?? '');

        if ($areaChanged && ! self::subAreaBelongsToArea($subAreaId, $areaId)) {
            return [$areaId, null, ''];
        }

        if ($areaId && ! self::subAreaBelongsToArea($subAreaId, $areaId)) {
            return [$areaId, null, ''];
        }

        return [$areaId, $subAreaId, $detectedSub];
    }

    private static function subAreaBelongsToArea($subAreaId, $areaId): bool
    {
        if (! self::pickScalar($subAreaId) || ! self::pickScalar($areaId)) {
            return false;
        }

        $subArea = SubArea::find($subAreaId);

        return $subArea && (int) $subArea->area_id === (int) $areaId;
    }

    private static function resolveCityIdForSnapshot($cityId, string $cityName, string $stateName, string $country): ?int
    {
        if (self::pickScalar($cityId)) {
            return (int) $cityId;
        }

        if ($cityName === '') {
            return null;
        }

        $query = City::query()->where('name', $cityName);
        if ($stateName !== '') {
            $query->where('state', $stateName);
        }
        $city = $query->first();
        if ($city) {
            return (int) $city->id;
        }

        if ($stateName === '') {
            return null;
        }

        $state = State::firstOrCreate(
            ['country' => $country, 'slug' => Str::slug($stateName)],
            ['name' => $stateName, 'status' => true]
        );

        $city = City::firstOrCreate(
            ['state_id' => $state->id, 'slug' => Str::slug($cityName)],
            [
                'name' => $cityName,
                'normalized_name' => self::normalized($cityName),
                'state' => $stateName,
                'country' => $country,
                'status' => true,
            ]
        );

        return (int) $city->id;
    }

    private static function publicLocationLabel(array $areaPayload, ?string $city, ?string $state, ?string $country): string
    {
        $rawParts = [
            $areaPayload['sub_area_name'] ?? null,
            $areaPayload['area_name'] ?? null,
            $city,
            $state,
            $country,
        ];

        $parts = [];
        foreach ($rawParts as $rawPart) {
            foreach (explode(',', (string) $rawPart) as $part) {
                $part = trim(preg_replace('/\s+/', ' ', $part));
                if ($part === '') {
                    continue;
                }
                $key = mb_strtolower($part);
                if (! isset($parts[$key])) {
                    $parts[$key] = $part;
                }
            }
        }

        return implode(', ', array_values($parts));
    }

    private static function applyAreaCityFilter(Builder $query, array $filters): void
    {
        $cityId = $filters['city_id'] ?? null;
        $city = self::clean($filters['city'] ?? '');
        $state = self::clean($filters['state'] ?? '');
        $country = self::clean($filters['country'] ?? '');

        if (! empty($cityId)) {
            $query->where(function ($filter) use ($cityId, $city, $state, $country) {
                $filter->where('city_id', $cityId)
                    ->orWhere(function ($fallback) use ($city, $state, $country) {
                        self::applySnapshotCityFallback($fallback, $city, $state, $country);
                    });
            });
            return;
        }

        if ($city === '' && $state === '' && $country === '') {
            return;
        }

        if ($city === '') {
            $query->when($state !== '', fn ($q) => $q->where('state', $state))
                ->when($country !== '', fn ($q) => $q->where('country', $country));
            return;
        }

        $cityIds = self::matchingCityIds($city, $state, $country);
        $query->where(function ($filter) use ($cityIds, $city, $state, $country) {
            if ($cityIds->isNotEmpty()) {
                $filter->whereIn('city_id', $cityIds);
            }

            $filter->orWhere(function ($fallback) use ($city, $state, $country) {
                self::applySnapshotCityFallback($fallback, $city, $state, $country);
            });
        });
    }

    private static function applySnapshotCityFallback(Builder $query, string $city, string $state = '', string $country = ''): void
    {
        $query->whereNull('city_id');

        if ($city !== '') {
            $query->where(function ($cityQuery) use ($city) {
                $cityQuery->where('city_name', $city)
                    ->orWhere('city', $city);
            });
        }

        if ($state !== '') {
            $query->where('state', $state);
        }

        if ($country !== '') {
            $query->where('country', $country);
        }
    }

    private static function matchingCityIds(string $city, string $state = '', string $country = '')
    {
        if ($city === '') {
            return collect();
        }

        return City::query()
            ->where(function ($query) use ($city) {
                $query->where('name', $city)
                    ->orWhere('slug', Str::slug($city));
            })
            ->when($state !== '', fn ($query) => $query->where('state', $state))
            ->when($country !== '', fn ($query) => $query->where('country', $country))
            ->pluck('id');
    }

    /**
     * @return mixed|null First non-null, non-empty scalar; otherwise null.
     */
    private static function pickScalar(...$vals)
    {
        foreach ($vals as $v) {
            if ($v === null || $v === '') {
                continue;
            }

            return $v;
        }

        return null;
    }

    private static function pickString(...$vals): string
    {
        foreach ($vals as $v) {
            if ($v === null || $v === '') {
                continue;
            }

            return (string) $v;
        }

        return '';
    }

    private static function clean($value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', (string) $value));
        return $value === '' ? '' : Str::title(Str::lower($value));
    }

    private static function normalized($value): string
    {
        return Str::of((string) $value)->lower()->squish()->value();
    }

    public static function resolveNearestFromCoordinates(
        float $lat,
        float $lng,
        ?int $cityId = null,
        string $cityName = '',
        string $stateName = '',
        string $country = 'India',
        array $names = []
    ): ?array {
        $cityId = self::resolveCityIdForSnapshot($cityId, $cityName, $stateName, $country);

        if (! $cityId) {
            return null;
        }

        $nameMatch = self::resolveFromAddressNames($names, $cityId, $cityName, $stateName, $country);
        if ($nameMatch) {
            return $nameMatch;
        }

        return null;
    }

    public static function resolveFromAddressNames(
        array $names,
        ?int $cityId = null,
        string $cityName = '',
        string $stateName = '',
        string $country = 'India'
    ): ?array {
        $cityId = self::resolveCityIdForSnapshot($cityId, $cityName, $stateName, $country);
        if (! $cityId) {
            return null;
        }

        $skip = array_filter([
            self::normalized($cityName),
            self::normalized($stateName),
            self::normalized($country),
            'india',
        ]);

        $locationNames = self::filterLocationNames($names, $skip);
        if ($locationNames === []) {
            return null;
        }

        $areas = Area::query()
            ->active()
            ->where('city_id', $cityId)
            ->with(['subAreas' => fn ($query) => $query->active()->orderBy('name')])
            ->orderBy('name')
            ->get();

        foreach ($locationNames as $normalized => $rawName) {
            foreach ($areas as $area) {
                $areaNorm = self::normalized($area->name);

                foreach ($area->subAreas as $subArea) {
                    $subNorm = self::normalized($subArea->name);
                    if ($normalized === $subNorm) {
                        return self::formatResolvedLocation($area, $subArea, 'name', null, $rawName);
                    }
                }

                if ($normalized === $areaNorm) {
                    return self::formatResolvedLocation($area, null, 'name', null, $rawName);
                }
            }
        }

        return null;
    }

    private static function filterLocationNames(array $names, array $skip): array
    {
        $locationNames = [];

        foreach ($names as $entry) {
            $name = is_array($entry) ? (string) ($entry['name'] ?? '') : (string) $entry;
            $types = is_array($entry) ? ($entry['types'] ?? []) : [];
            $normalized = self::normalized($name);

            if ($normalized === '' || in_array($normalized, $skip, true)) {
                continue;
            }

            if ($types !== []) {
                $allowed = ['sublocality', 'sublocality_level_1', 'sublocality_level_2', 'sublocality_level_3', 'neighborhood', 'route', 'premise', 'street_address', 'establishment', 'point_of_interest'];
                if (! array_intersect($types, $allowed)) {
                    continue;
                }
            }

            $locationNames[$normalized] = $name;
        }

        return $locationNames;
    }

    private static function formatResolvedLocation(Area $area, ?SubArea $subArea, string $matchType, ?float $distanceKm = null, ?string $matchedName = null): array
    {
        return [
            'area_id' => (int) $area->id,
            'sub_area_id' => $subArea ? (int) $subArea->id : null,
            'city_id' => $area->city_id ? (int) $area->city_id : null,
            'area_name' => $area->name,
            'sub_area_name' => $subArea?->name,
            'detected_area_name' => $area->name,
            'detected_sub_area_name' => $subArea?->name,
            'match_type' => $matchType,
            'distance_km' => $distanceKm !== null ? round($distanceKm, 2) : null,
            'matched_name' => $matchedName,
            'confidence' => $matchType === 'name' ? 'high' : ($distanceKm !== null && $distanceKm <= 5 ? 'high' : 'low'),
        ];
    }

    private static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $latFrom = deg2rad($lat1);
        $latTo = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        return $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
