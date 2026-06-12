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
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AreaListingService
{
    public static function resolveAreaListingActor(): ?object
    {
        $user = Auth::guard('sanctum')->user();
        if ($user) {
            return $user;
        }

        $user = Auth::user();
        if ($user) {
            return $user;
        }

        return auth()->user();
    }

    public static function resolveAreaListingActorId(): ?int
    {
        $actor = self::resolveAreaListingActor();
        if (! $actor) {
            return null;
        }

        $id = $actor->id ?? null;
        return $id !== null && $id !== '' ? (int) $id : null;
    }

    public static function canAutoCreateAreasOnSave(?Request $request = null): bool
    {
        $request = $request ?? request();

        if ($request->boolean('area_listing_admin_save')) {
            return true;
        }

        if (self::requestIsUserPortal($request)) {
            return false;
        }

        return self::userCanAutoCreateAreas(self::resolveAreaListingActor());
    }

    /** End-user dashboard saves suggest only; never auto-create or auto-link master areas. */
    public static function requestIsUserPortal(?Request $request = null): bool
    {
        $request = $request ?? request();

        if ($request->boolean('area_listing_admin_save')) {
            return false;
        }

        if ($request->boolean('area_listing_user_portal') || $request->boolean('area_listing_user_suggested')) {
            return true;
        }

        $actor = self::resolveAreaListingActor();
        if ($actor instanceof Customer && ! self::userCanAutoCreateAreas($actor)) {
            return true;
        }

        return false;
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

        if ($user instanceof Customer) {
            return (bool) $user->is_agent && (bool) $user->can_manage_area_listing;
        }

        if (method_exists($user, 'getTable') && $user->getTable() === 'customers') {
            return (bool) ($user->is_agent ?? false) && (bool) ($user->can_manage_area_listing ?? false);
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

        $pendingExists = DB::table('area_listing_suggestions')
            ->where('type', $type)
            ->where('status', 'pending')
            ->where('normalized_name', $normalized)
            ->when($type === 'sub_area', function ($q) use ($areaId) {
                if ($areaId) {
                    $q->where(function ($inner) use ($areaId) {
                        $inner->where('area_id', $areaId)->orWhereNull('area_id');
                    });
                }
            })
            ->exists();

        if ($pendingExists) {
            $updatePayload = [
                'name' => $name,
                'slug' => Str::slug($name),
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'country' => $data['country'] ?? 'India',
                'suggested_by' => self::resolveAreaListingActorId(),
                'updated_at' => now(),
            ];
            if ($type === 'sub_area' && $areaId) {
                $updatePayload['area_id'] = $areaId;
            }
            DB::table('area_listing_suggestions')
                ->where('type', $type)
                ->where('status', 'pending')
                ->where('normalized_name', $normalized)
                ->when($type === 'sub_area', function ($q) use ($areaId) {
                    if ($areaId) {
                        $q->where(function ($inner) use ($areaId) {
                            $inner->where('area_id', $areaId)->orWhereNull('area_id');
                        });
                    }
                })
                ->update($updatePayload);

            return;
        }

        DB::table('area_listing_suggestions')->insert([
            'type' => $type,
            'area_id' => $areaId,
            'normalized_name' => $normalized,
            'status' => 'pending',
            'name' => $name,
            'slug' => Str::slug($name),
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? 'India',
            'suggested_by' => self::resolveAreaListingActorId(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

    /** Carry user-portal / admin-save flags into merged request used by saveListingLocation. */
    private static function mergeAreaListingPortalFlags(Request $source, Request $merged): Request
    {
        $extra = [];
        if ($source->boolean('area_listing_user_suggested')) {
            $extra['area_listing_user_suggested'] = 1;
        }
        if (self::requestIsUserPortal($source)) {
            $extra['area_listing_user_portal'] = 1;
        }
        if ($source->boolean('area_listing_admin_save')) {
            $extra['area_listing_admin_save'] = 1;
        }

        $manualAddress = self::pickManualAddressFromRequest($source);
        if ($manualAddress === '') {
            $manualAddress = self::pickManualAddressFromRequest($merged);
        }
        if ($manualAddress !== '') {
            $extra['client_address'] = $manualAddress;
            $extra['customer_address'] = $manualAddress;
            $extra['manual_address'] = $manualAddress;
        }

        if ($extra === []) {
            return $merged;
        }

        return Request::create('/', 'POST', array_merge($merged->all(), $extra));
    }

    private static function pickManualAddressFromRequest(Request $request): string
    {
        return self::clean(
            $request->input('client_address')
            ?: $request->input('customer_address')
            ?: $request->input('manual_address')
            ?: ''
        );
    }

    public static function savePropertyLocation($property, Request $request): void
    {
        $merged = self::buildMergedPropertyAreaListingRequest($property, $request);
        $merged = self::mergeAreaListingPortalFlags($request, $merged);
        self::saveListingLocation('area_listing_property_locations', 'property_id', $property->id, $merged);
    }

    /**
     * User portal may send area_id/sub_area_id keys as empty strings; treat those as "not provided"
     * so merge logic does not wipe stored IDs on edit.
     */
    /**
     * User portal edits often omit area_id/sub_area_id unless the user picked from the master list.
     * If detected names changed vs the saved snapshot, drop stale master IDs so save creates new suggestions.
     */
    private static function applyUserPortalMasterIdRules(
        Request $request,
        ?object $existing,
        $areaId,
        $subAreaId,
        string $detectedArea,
        string $detectedSub,
        bool $hasIncomingAreaIdPayload,
        bool $hasIncomingSubAreaIdPayload
    ): array {
        if (! self::requestIsUserPortal($request)) {
            return [$areaId, $subAreaId];
        }

        $newAreaNorm = self::normalized($detectedArea);
        if (! empty($areaId) && $newAreaNorm !== '') {
            $linkedArea = Area::find($areaId);
            if ($linkedArea && $newAreaNorm !== self::normalized($linkedArea->name)) {
                $areaId = null;
                $subAreaId = null;
            }
        } elseif ($existing && $newAreaNorm !== '') {
            $savedAreaNorm = self::normalized(self::clean((string) ($existing->area_name ?: $existing->detected_area_name ?: '')));
            $linkedAreaNorm = '';
            if (! empty($existing->area_id)) {
                $linkedArea = Area::find($existing->area_id);
                $linkedAreaNorm = $linkedArea ? self::normalized($linkedArea->name) : '';
            }
            $areaUnchanged = $newAreaNorm === $savedAreaNorm
                || ($linkedAreaNorm !== '' && $newAreaNorm === $linkedAreaNorm);
            if (! $areaUnchanged && ! $hasIncomingAreaIdPayload) {
                $areaId = null;
                $subAreaId = null;
            }
        }

        $newSubNorm = self::normalized($detectedSub);
        if (! empty($subAreaId) && $newSubNorm !== '') {
            $linkedSub = SubArea::find($subAreaId);
            if ($linkedSub && $newSubNorm !== self::normalized($linkedSub->name)) {
                $subAreaId = null;
            }
        } elseif ($existing && $newSubNorm !== '' && ! $hasIncomingSubAreaIdPayload) {
            $savedSubNorm = self::normalized(self::clean((string) ($existing->sub_area_name ?: $existing->detected_sub_area_name ?: '')));
            if ($newSubNorm !== $savedSubNorm) {
                $subAreaId = null;
            }
        }

        return [$areaId, $subAreaId];
    }

    /**
     * User portal must not keep master IDs when labels no longer match the linked records.
     */
    private static function sanitizeUserPortalMasterIds(
        $areaId,
        $subAreaId,
        string $detectedArea,
        string $detectedSubArea
    ): array {
        $selectedArea = ! empty($areaId) ? Area::find($areaId) : null;
        $selectedSubArea = ! empty($subAreaId) ? SubArea::find($subAreaId) : null;

        $detectedAreaNorm = self::normalized($detectedArea);
        if ($selectedArea && $detectedAreaNorm !== '' && $detectedAreaNorm !== self::normalized($selectedArea->name)) {
            $areaId = null;
            $subAreaId = null;
            $selectedArea = null;
            $selectedSubArea = null;
        }

        $detectedSubNorm = self::normalized($detectedSubArea);
        if ($selectedSubArea && $detectedSubNorm !== '' && $detectedSubNorm !== self::normalized($selectedSubArea->name)) {
            $subAreaId = null;
            $selectedSubArea = null;
        }

        return [$areaId, $subAreaId, $selectedArea, $selectedSubArea];
    }

    private static function requestProvidedListingId(
        Request $request,
        array $loc,
        string $key,
        bool $locProvided,
        bool $topProvided
    ): bool {
        if (self::requestIsUserPortal($request)) {
            $value = self::pickScalar(
                $locProvided ? ($loc[$key] ?? null) : null,
                $topProvided ? $request->input($key) : null
            );

            return $value !== null && $value !== '';
        }

        return $locProvided || $topProvided;
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

        $hasIncomingAreaIdPayload = self::requestProvidedListingId($request, $loc, 'area_id', $locAreaProvided, $topAreaProvided);
        $hasIncomingSubAreaIdPayload = self::requestProvidedListingId($request, $loc, 'sub_area_id', $locSubProvided, $topSubProvided);

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

        [$areaId, $subAreaId, $detectedSub] = self::dropMismatchedSubAreaSelection(
            $areaId,
            $subAreaId,
            $detectedSub,
            $existing,
            $hasIncomingAreaIdPayload,
            $incomingAreaId
        );

        [$areaId, $subAreaId] = self::applyUserPortalMasterIdRules(
            $request,
            $existing,
            $areaId,
            $subAreaId,
            $detectedArea,
            $detectedSub,
            $hasIncomingAreaIdPayload,
            $hasIncomingSubAreaIdPayload
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

        $cityName = self::normalizeCityNameForListing($cityName, $stateName, $country);

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
            $request->input('client_address'),
            $request->input('customer_address'),
            array_key_exists('manual_address', $loc) ? ($loc['manual_address'] ?? null) : null,
            $existing?->manual_address ?? null
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
            'client_address' => $manualAddress !== '' ? $manualAddress : null,
            'customer_address' => $manualAddress !== '' ? $manualAddress : null,
            'area_listing_source' => $source,
            'location_is_verified' => $request->has('location_is_verified')
                ? $request->input('location_is_verified')
                : ($loc['location_is_verified'] ?? null),
            'area_listing_user_portal' => self::requestIsUserPortal($request) ? 1 : 0,
            'area_listing_user_suggested' => $request->boolean('area_listing_user_suggested') ? 1 : 0,
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

        $hasIncomingAreaIdPayload = self::requestProvidedListingId($request, $loc, 'area_id', $locAreaProvided, $topAreaProvided);
        $hasIncomingSubAreaIdPayload = self::requestProvidedListingId($request, $loc, 'sub_area_id', $locSubProvided, $topSubProvided);

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

        [$areaId, $subAreaId, $detectedSub] = self::dropMismatchedSubAreaSelection(
            $areaId,
            $subAreaId,
            $detectedSub,
            $existing,
            $hasIncomingAreaIdPayload,
            $incomingAreaId
        );

        [$areaId, $subAreaId] = self::applyUserPortalMasterIdRules(
            $request,
            $existing,
            $areaId,
            $subAreaId,
            $detectedArea,
            $detectedSub,
            $hasIncomingAreaIdPayload,
            $hasIncomingSubAreaIdPayload
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
            $request->input('client_address'),
            $request->input('customer_address'),
            array_key_exists('manual_address', $loc) ? ($loc['manual_address'] ?? null) : null,
            $existing?->manual_address ?? null
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
            'client_address' => $manualAddress !== '' ? $manualAddress : null,
            'customer_address' => $manualAddress !== '' ? $manualAddress : null,
            'area_listing_source' => $source,
            'location_is_verified' => $request->has('location_is_verified')
                ? $request->input('location_is_verified')
                : ($loc['location_is_verified'] ?? null),
            'area_listing_user_portal' => self::requestIsUserPortal($request) ? 1 : 0,
            'area_listing_user_suggested' => $request->boolean('area_listing_user_suggested') ? 1 : 0,
        ];

        return Request::create('/', 'POST', $payload);
    }

    public static function saveProjectLocation($project, Request $request): void
    {
        $merged = self::buildMergedProjectAreaListingRequest($project, $request);
        $merged = self::mergeAreaListingPortalFlags($request, $merged);
        self::saveListingLocation('area_listing_project_locations', 'project_id', $project->id, $merged);
    }

    /**
     * Create master area/sub-area row from an Area Wise suggestion (admin approve or repair).
     */
    public static function createMasterFromSuggestionRecord(object $suggestion): Area|SubArea|null
    {
        $normalized = self::clean((string) ($suggestion->normalized_name ?? ''));
        $name = self::clean((string) ($suggestion->name ?? ''));
        if ($normalized === '' || $name === '') {
            return null;
        }

        if ($suggestion->type === 'area') {
            $context = self::resolveCityContextFromSuggestion($suggestion);
            if (empty($context['city_id'])) {
                return null;
            }

            return Area::updateOrCreate(
                ['city_id' => $context['city_id'], 'normalized_name' => $normalized],
                array_merge($context, [
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'normalized_name' => $normalized,
                    'status' => true,
                    'workflow_status' => 'active',
                    'archived_at' => null,
                ])
            );
        }

        if ($suggestion->type !== 'sub_area') {
            return null;
        }

        $areaId = self::resolveAreaIdForSubSuggestion($suggestion);
        if (! $areaId) {
            return null;
        }

        return SubArea::updateOrCreate(
            ['area_id' => $areaId, 'normalized_name' => $normalized],
            [
                'area_id' => $areaId,
                'name' => $name,
                'slug' => Str::slug($name),
                'normalized_name' => $normalized,
                'status' => true,
                'workflow_status' => 'active',
                'archived_at' => null,
            ]
        );
    }

    /** Create master rows for suggestions that were closed without a DB record (listing auto-resolve bug). */
    public static function repairSuggestionsApprovedWithoutMaster(): int
    {
        $created = 0;
        $rows = DB::table('area_listing_suggestions')
            ->where('status', 'approved')
            ->where('review_note', 'like', 'Auto-resolved when listing was approved%')
            ->orderBy('id')
            ->get();

        foreach ($rows as $suggestion) {
            if (self::masterRecordExistsForSuggestion($suggestion)) {
                continue;
            }
            if (self::createMasterFromSuggestionRecord($suggestion)) {
                self::propagateApprovedSuggestion($suggestion);
                $created++;
            }
        }

        return $created;
    }

    /** Link listing location rows to existing master area/sub-area records by name. */
    public static function syncUnlinkedListingLocations(): int
    {
        $synced = 0;

        $projectIds = DB::table('area_listing_project_locations')
            ->where(function ($query) {
                $query->where(function ($areaQ) {
                    $areaQ->whereNull('area_id')
                        ->where(function ($nameQ) {
                            $nameQ->whereNotNull('detected_area_name')->where('detected_area_name', '!=', '')
                                ->orWhereNotNull('area_name')->where('area_name', '!=', '');
                        });
                })->orWhere(function ($subQ) {
                    $subQ->whereNull('sub_area_id')
                        ->where(function ($nameQ) {
                            $nameQ->whereNotNull('detected_sub_area_name')->where('detected_sub_area_name', '!=', '')
                                ->orWhereNotNull('sub_area_name')->where('sub_area_name', '!=', '');
                        });
                });
            })
            ->pluck('project_id');

        foreach ($projectIds as $id) {
            $before = DB::table('area_listing_project_locations')->where('project_id', $id)->first();
            self::materializeLocationOnListingApproval((int) $id, 'project');
            $after = DB::table('area_listing_project_locations')->where('project_id', $id)->first();
            if ($before && $after && (
                (string) ($before->area_id ?? '') !== (string) ($after->area_id ?? '')
                || (string) ($before->sub_area_id ?? '') !== (string) ($after->sub_area_id ?? '')
            )) {
                $synced++;
            }
        }

        $propertyIds = DB::table('area_listing_property_locations')
            ->where(function ($query) {
                $query->where(function ($areaQ) {
                    $areaQ->whereNull('area_id')
                        ->where(function ($nameQ) {
                            $nameQ->whereNotNull('detected_area_name')->where('detected_area_name', '!=', '')
                                ->orWhereNotNull('area_name')->where('area_name', '!=', '');
                        });
                })->orWhere(function ($subQ) {
                    $subQ->whereNull('sub_area_id')
                        ->where(function ($nameQ) {
                            $nameQ->whereNotNull('detected_sub_area_name')->where('detected_sub_area_name', '!=', '')
                                ->orWhereNotNull('sub_area_name')->where('sub_area_name', '!=', '');
                        });
                });
            })
            ->pluck('property_id');

        foreach ($propertyIds as $id) {
            $before = DB::table('area_listing_property_locations')->where('property_id', $id)->first();
            self::materializeLocationOnListingApproval((int) $id, 'property');
            $after = DB::table('area_listing_property_locations')->where('property_id', $id)->first();
            if ($before && $after && (
                (string) ($before->area_id ?? '') !== (string) ($after->area_id ?? '')
                || (string) ($before->sub_area_id ?? '') !== (string) ($after->sub_area_id ?? '')
            )) {
                $synced++;
            }
        }

        return $synced;
    }

    public static function masterRecordExistsForSuggestion(object $suggestion): bool
    {
        $normalized = self::clean((string) ($suggestion->normalized_name ?? ''));
        if ($normalized === '') {
            return false;
        }

        if ($suggestion->type === 'area') {
            $context = self::resolveCityContextFromSuggestion($suggestion);
            $cityId = $context['city_id'] ?? null;

            return $cityId
                ? (bool) self::findExistingAreaForCity((int) $cityId, $normalized)
                : Area::query()->active()->where('normalized_name', $normalized)->exists();
        }

        if ($suggestion->type === 'sub_area') {
            $areaId = self::resolveAreaIdForSubSuggestion($suggestion);

            return $areaId
                ? (bool) self::findExistingSubAreaForArea($areaId, $normalized)
                : SubArea::query()->active()->where('normalized_name', $normalized)->exists();
        }

        return false;
    }

    /**
     * On listing approval: create or link master area/sub-area from the listing location,
     * then close matching pending suggestions (no separate suggestion approval needed).
     */
    public static function materializeLocationOnListingApproval(int $listingId, string $listingType): void
    {
        $table = $listingType === 'project'
            ? 'area_listing_project_locations'
            : 'area_listing_property_locations';
        $foreignKey = $listingType === 'project' ? 'project_id' : 'property_id';

        $row = DB::table($table)->where($foreignKey, $listingId)->first();
        if (! $row) {
            return;
        }

        $updates = [];
        $stateName = self::clean((string) ($row->state ?? ''));
        $cityName = self::clean((string) ($row->city ?? ''));
        $country = self::clean((string) ($row->country ?? 'India')) ?: 'India';
        $cityName = self::normalizeCityNameForListing($cityName, $stateName, $country);
        $cityId = self::resolveCityIdForSnapshot($row->city_id ?? null, $cityName, $stateName, $country);
        $areaId = self::pickScalar($row->area_id ?? null) ? (int) $row->area_id : null;
        $subAreaId = self::pickScalar($row->sub_area_id ?? null) ? (int) $row->sub_area_id : null;

        if (! $areaId) {
            $areaName = self::clean((string) ($row->area_name ?: $row->detected_area_name ?: ''));
            if ($areaName !== '' && $cityId) {
                $areaNorm = self::normalized($areaName);
                $existingArea = self::findExistingAreaForCity($cityId, $areaNorm);
                if ($existingArea) {
                    $areaId = (int) $existingArea->id;
                    $updates['area_id'] = $areaId;
                    $updates['area_name'] = $existingArea->name;
                } else {
                    $state = $stateName !== '' ? State::firstOrCreate(
                        ['country' => $country, 'slug' => Str::slug($stateName)],
                        ['name' => $stateName, 'status' => true]
                    ) : null;
                    $area = Area::firstOrCreate(
                        ['city_id' => $cityId, 'normalized_name' => $areaNorm],
                        [
                            'state_id' => $state?->id,
                            'city' => $cityName,
                            'city_name' => $cityName,
                            'state' => $stateName,
                            'country' => $country,
                            'name' => $areaName,
                            'slug' => Str::slug($areaName),
                            'status' => true,
                            'workflow_status' => 'active',
                        ]
                    );
                    $areaId = (int) $area->id;
                    $updates['area_id'] = $areaId;
                    $updates['area_name'] = $area->name;
                }
            }
        }

        if (! $subAreaId) {
            $subName = self::clean((string) ($row->sub_area_name ?: $row->detected_sub_area_name ?: ''));
            if ($subName !== '' && $areaId) {
                $subNorm = self::normalized($subName);
                $existingSub = self::findExistingSubAreaForArea($areaId, $subNorm);
                if ($existingSub) {
                    $subAreaId = (int) $existingSub->id;
                    $updates['sub_area_id'] = $subAreaId;
                    $updates['sub_area_name'] = $existingSub->name;
                } else {
                    $subArea = SubArea::firstOrCreate(
                        ['area_id' => $areaId, 'normalized_name' => $subNorm],
                        [
                            'name' => $subName,
                            'slug' => Str::slug($subName),
                            'status' => true,
                            'workflow_status' => 'active',
                        ]
                    );
                    $subAreaId = (int) $subArea->id;
                    $updates['sub_area_id'] = $subAreaId;
                    $updates['sub_area_name'] = $subArea->name;
                }
            }
        }

        if ($updates !== []) {
            $updates['updated_at'] = now();
            DB::table($table)->where($foreignKey, $listingId)->update($updates);
            $row = DB::table($table)->where($foreignKey, $listingId)->first();
        }

        if ($row) {
            self::markPendingSuggestionsResolvedOnListingApproval($row);
        }
    }

    private static function markPendingSuggestionsResolvedOnListingApproval(object $row): void
    {
        $cityName = self::clean((string) ($row->city ?? ''));
        $areaId = self::pickScalar($row->area_id ?? null) ? (int) $row->area_id : null;
        $note = 'Materialized when listing was approved';

        $areaName = self::clean((string) ($row->area_name ?: $row->detected_area_name ?: ''));
        if ($areaName !== '') {
            self::markPendingSuggestionsResolvedByMaster('area', self::normalized($areaName), null, $cityName, $note);
        }

        $subName = self::clean((string) ($row->sub_area_name ?: $row->detected_sub_area_name ?: ''));
        if ($subName !== '') {
            self::markPendingSuggestionsResolvedByMaster('sub_area', self::normalized($subName), $areaId, '', $note);
        }
    }

    /**
     * After admin approves a suggestion in Area Wise: link matching listings and close duplicates.
     */
    public static function propagateApprovedSuggestion(object $suggestion): void
    {
        $normalized = self::clean((string) ($suggestion->normalized_name ?? ''));
        if ($normalized === '') {
            return;
        }

        if ($suggestion->type === 'area') {
            $cityName = self::clean((string) ($suggestion->city ?? ''));
            $cityId = null;
            if ($cityName !== '') {
                $cityQuery = City::query()->whereRaw('LOWER(TRIM(name)) = ?', [self::normalized($cityName)]);
                $stateName = self::clean((string) ($suggestion->state ?? ''));
                if ($stateName !== '') {
                    $cityQuery->whereRaw('LOWER(TRIM(state)) = ?', [self::normalized($stateName)]);
                }
                $cityId = $cityQuery->value('id');
            }

            $master = $cityId
                ? self::findExistingAreaForCity((int) $cityId, $normalized)
                : Area::query()->active()->where('normalized_name', $normalized)->first();

            if (! $master) {
                return;
            }

            $areaId = (int) $master->id;
            $linkPayload = [
                'area_id' => $areaId,
                'area_name' => $master->name,
                'updated_at' => now(),
            ];

            foreach (['area_listing_project_locations', 'area_listing_property_locations'] as $locTable) {
                DB::table($locTable)
                    ->whereNull('area_id')
                    ->where(function ($q) use ($normalized) {
                        $q->whereRaw('LOWER(TRIM(detected_area_name)) = ?', [$normalized])
                            ->orWhereRaw('LOWER(TRIM(area_name)) = ?', [$normalized]);
                    })
                    ->when($cityId, fn ($q) => $q->where('city_id', $cityId))
                    ->update($linkPayload);
            }

            self::markPendingSuggestionsResolvedByMaster('area', $normalized, null, $cityName);
            return;
        }

        if ($suggestion->type !== 'sub_area') {
            return;
        }

        $areaId = self::pickScalar($suggestion->area_id ?? null) ? (int) $suggestion->area_id : null;
        $master = $areaId
            ? self::findExistingSubAreaForArea($areaId, $normalized)
            : SubArea::query()->active()->where('normalized_name', $normalized)->first();

        if (! $master) {
            return;
        }

        $subAreaId = (int) $master->id;
        $parentAreaId = (int) $master->area_id;
        $linkPayload = [
            'sub_area_id' => $subAreaId,
            'sub_area_name' => $master->name,
            'updated_at' => now(),
        ];
        if ($parentAreaId > 0) {
            $linkPayload['area_id'] = $parentAreaId;
            $parentArea = Area::find($parentAreaId);
            if ($parentArea) {
                $linkPayload['area_name'] = $parentArea->name;
            }
        }

        foreach (['area_listing_project_locations', 'area_listing_property_locations'] as $locTable) {
            $query = DB::table($locTable)
                ->whereNull('sub_area_id')
                ->where(function ($q) use ($normalized) {
                    $q->whereRaw('LOWER(TRIM(detected_sub_area_name)) = ?', [$normalized])
                        ->orWhereRaw('LOWER(TRIM(sub_area_name)) = ?', [$normalized]);
                });
            if ($areaId) {
                $query->where(function ($areaQ) use ($areaId) {
                    $areaQ->where('area_id', $areaId)->orWhereNull('area_id');
                });
            }
            $query->update($linkPayload);
        }

        self::markPendingSuggestionsResolvedByMaster('sub_area', $normalized, $areaId);
    }

    /** Close pending suggestions that already exist in master tables (stale queue cleanup). */
    public static function resolveStalePendingSuggestions(): int
    {
        $closed = 0;
        $pending = DB::table('area_listing_suggestions')->where('status', 'pending')->get();

        foreach ($pending as $suggestion) {
            $exists = false;
            if ($suggestion->type === 'area') {
                $cityId = null;
                $cityName = self::clean((string) ($suggestion->city ?? ''));
                if ($cityName !== '') {
                    $cityId = City::query()
                        ->whereRaw('LOWER(TRIM(name)) = ?', [self::normalized($cityName)])
                        ->value('id');
                }
                $exists = $cityId
                    ? (bool) self::findExistingAreaForCity((int) $cityId, (string) $suggestion->normalized_name)
                    : Area::query()->active()->where('normalized_name', $suggestion->normalized_name)->exists();
            } elseif ($suggestion->type === 'sub_area') {
                $areaId = self::pickScalar($suggestion->area_id ?? null) ? (int) $suggestion->area_id : null;
                $exists = $areaId
                    ? (bool) self::findExistingSubAreaForArea($areaId, (string) $suggestion->normalized_name)
                    : SubArea::query()->active()->where('normalized_name', $suggestion->normalized_name)->exists();
            }

            if ($exists) {
                self::markPendingSuggestionsResolvedByMaster(
                    (string) $suggestion->type,
                    (string) $suggestion->normalized_name,
                    self::pickScalar($suggestion->area_id ?? null) ? (int) $suggestion->area_id : null,
                    self::clean((string) ($suggestion->city ?? ''))
                );
                $closed++;
            }
        }

        return $closed;
    }

    private static function markPendingSuggestionsResolvedByMaster(
        string $type,
        string $normalized,
        ?int $areaId = null,
        string $cityName = '',
        string $reviewNote = 'Auto-resolved: master record already exists'
    ): void {
        $reviewPayload = [
            'status' => 'approved',
            'review_note' => $reviewNote,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'updated_at' => now(),
        ];

        $query = DB::table('area_listing_suggestions')
            ->where('type', $type)
            ->where('status', 'pending')
            ->where('normalized_name', $normalized);

        if ($type === 'sub_area' && $areaId) {
            $query->where(function ($areaQ) use ($areaId) {
                $areaQ->where('area_id', $areaId)->orWhereNull('area_id');
            });
        }

        if ($type === 'area' && $cityName !== '') {
            $cityNorm = self::normalized($cityName);
            $query->where(function ($cityQ) use ($cityNorm) {
                $cityQ->whereNull('city')
                    ->orWhereRaw('LOWER(TRIM(city)) = ?', [$cityNorm]);
            });
        }

        $query->update($reviewPayload);
    }

    /** After user portal save: close suggestions already in master DB; attach parent area to pending sub rows. */
    private static function reconcilePendingSuggestionsAfterUserPortalSave(object $row): void
    {
        self::markPendingSuggestionsResolvedForLocation($row);

        $areaId = self::pickScalar($row->area_id ?? null) ? (int) $row->area_id : null;
        $subNorm = self::normalized(self::clean((string) ($row->detected_sub_area_name ?: $row->sub_area_name ?: '')));
        if ($areaId && $subNorm !== '' && ! self::pickScalar($row->sub_area_id ?? null)) {
            DB::table('area_listing_suggestions')
                ->where('type', 'sub_area')
                ->where('status', 'pending')
                ->where('normalized_name', $subNorm)
                ->where(function ($q) use ($areaId) {
                    $q->whereNull('area_id')->orWhere('area_id', $areaId);
                })
                ->update(['area_id' => $areaId, 'updated_at' => now()]);
        }
    }

    private static function markPendingSuggestionsResolvedForLocation(object $row): void
    {
        $cityId = self::pickScalar($row->city_id ?? null) ? (int) $row->city_id : null;
        $cityName = self::clean((string) ($row->city ?? ''));

        $areaName = self::clean((string) ($row->area_name ?: $row->detected_area_name ?: ''));
        if ($areaName !== '') {
            $normalized = self::normalized($areaName);
            $masterExists = false;
            if (self::pickScalar($row->area_id ?? null)) {
                $masterExists = Area::query()->active()->where('id', (int) $row->area_id)->exists();
            } elseif ($cityId) {
                $masterExists = (bool) self::findExistingAreaForCity($cityId, $normalized);
            } else {
                $masterExists = Area::query()->active()->where('normalized_name', $normalized)->exists();
            }
            if ($masterExists) {
                self::markPendingSuggestionsResolvedByMaster('area', $normalized, null, $cityName);
            }
        }

        $subName = self::clean((string) ($row->sub_area_name ?: $row->detected_sub_area_name ?: ''));
        if ($subName !== '') {
            $normalized = self::normalized($subName);
            $areaId = self::pickScalar($row->area_id ?? null) ? (int) $row->area_id : null;
            $masterExists = false;
            if (self::pickScalar($row->sub_area_id ?? null)) {
                $masterExists = SubArea::query()->active()->where('id', (int) $row->sub_area_id)->exists();
            } elseif ($areaId) {
                $masterExists = (bool) self::findExistingSubAreaForArea($areaId, $normalized);
            } else {
                $masterExists = SubArea::query()->active()->where('normalized_name', $normalized)->exists();
            }
            if ($masterExists) {
                self::markPendingSuggestionsResolvedByMaster('sub_area', $normalized, $areaId);
            }
        }
    }

    private static function resolveCityContextFromSuggestion(object $suggestion): array
    {
        $cityName = self::clean((string) ($suggestion->city ?? ''));
        $stateName = self::clean((string) ($suggestion->state ?? ''));
        $country = self::clean((string) ($suggestion->country ?? 'India')) ?: 'India';

        $cityModel = null;
        if ($cityName !== '') {
            $cityQuery = City::query()->whereRaw('LOWER(TRIM(name)) = ?', [self::normalized($cityName)]);
            if ($stateName !== '') {
                $cityQuery->whereRaw('LOWER(TRIM(state)) = ?', [self::normalized($stateName)]);
            }
            $cityModel = $cityQuery->first();
        }

        if (! $cityModel && $cityName !== '' && $stateName !== '') {
            $stateModel = State::firstOrCreate(
                ['country' => $country, 'slug' => Str::slug($stateName)],
                ['name' => $stateName, 'status' => true]
            );
            $cityModel = City::firstOrCreate(
                ['state_id' => $stateModel->id, 'slug' => Str::slug($cityName)],
                ['name' => $cityName, 'state' => $stateName, 'country' => $country, 'status' => true]
            );
        }

        return [
            'state_id' => $cityModel?->state_id,
            'city_id' => $cityModel?->id,
            'city_name' => $cityModel?->name ?: $cityName,
            'city' => $cityModel?->name ?: $cityName,
            'state' => $cityModel?->state ?: $stateName,
            'country' => $cityModel?->country ?: $country,
        ];
    }

    private static function resolveAreaIdForSubSuggestion(object $suggestion): ?int
    {
        if (self::pickScalar($suggestion->area_id ?? null)) {
            return (int) $suggestion->area_id;
        }

        $parentHint = self::clean((string) ($suggestion->city ?? ''));
        if ($parentHint !== '') {
            $norm = self::normalized($parentHint);
            $area = Area::query()
                ->active()
                ->where(function ($q) use ($norm) {
                    $q->where('normalized_name', $norm)
                        ->orWhereRaw('LOWER(TRIM(name)) = ?', [$norm]);
                })
                ->first();
            if ($area) {
                return (int) $area->id;
            }
        }

        $subNorm = self::clean((string) ($suggestion->normalized_name ?? ''));
        if ($subNorm !== '') {
            $loc = DB::table('area_listing_project_locations')
                ->where(function ($q) use ($subNorm) {
                    $q->whereRaw('LOWER(TRIM(detected_sub_area_name)) = ?', [$subNorm])
                        ->orWhereRaw('LOWER(TRIM(sub_area_name)) = ?', [$subNorm]);
                })
                ->whereNotNull('area_id')
                ->value('area_id');

            if ($loc) {
                return (int) $loc;
            }

            $loc = DB::table('area_listing_property_locations')
                ->where(function ($q) use ($subNorm) {
                    $q->whereRaw('LOWER(TRIM(detected_sub_area_name)) = ?', [$subNorm])
                        ->orWhereRaw('LOWER(TRIM(sub_area_name)) = ?', [$subNorm]);
                })
                ->whereNotNull('area_id')
                ->value('area_id');

            if ($loc) {
                return (int) $loc;
            }
        }

        return null;
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
        $isUserPortal = self::requestIsUserPortal($request);

        // Admin forms always send sub_area_id; empty select means no ID — keep detected_sub_area_name for save-time auto-create.
        if ($request->has('sub_area_id') && ! self::pickScalar($subAreaId)) {
            $subAreaId = null;
        }

        $selectedArea = ! empty($areaId) ? Area::find($areaId) : null;
        $selectedSubArea = ! empty($subAreaId) ? SubArea::find($subAreaId) : null;

        if ($isUserPortal) {
            [$areaId, $subAreaId, $selectedArea, $selectedSubArea] = self::sanitizeUserPortalMasterIds(
                $areaId,
                $subAreaId,
                $detectedArea,
                $detectedSubArea
            );
        }

        if ($selectedSubArea && $selectedArea && (int) $selectedSubArea->area_id !== (int) $selectedArea->id) {
            $subAreaId = null;
            $selectedSubArea = null;
        }
        if (! $selectedArea && $selectedSubArea) {
            $selectedArea = Area::find($selectedSubArea->area_id);
            $areaId = $selectedArea?->id ?: $areaId;
        }

        $selectedCity = $request->input('city_id') ? City::find($request->input('city_id')) : null;
        if ($selectedArea) {
            if (! empty($selectedArea->city_id)) {
                $selectedCity = City::find($selectedArea->city_id) ?: $selectedCity;
                if ($selectedCity) {
                    $cityName = self::clean($selectedCity->name);
                    $stateName = self::clean($selectedCity->state ?: $stateName);
                    $country = self::clean($selectedCity->country ?: $country ?: 'India');
                } else {
                    $cityName = self::clean($selectedArea->city_name ?: $selectedArea->city ?: $cityName);
                    $stateName = self::clean($selectedArea->state ?: $stateName);
                    $country = self::clean($selectedArea->country ?: $country ?: 'India');
                }
            } else {
                $stateName = self::clean($selectedArea->state ?: $stateName);
                $country = self::clean($selectedArea->country ?: $country ?: 'India');
                $cityName = self::clean($selectedArea->city_name ?: $selectedArea->city ?: $cityName);
            }
        } elseif ($selectedCity) {
            $cityName = self::clean($selectedCity->name ?: $cityName);
            $stateName = self::clean($selectedCity->state ?: $stateName);
            $country = self::clean($selectedCity->country ?: $country ?: 'India');
        }

        $cityName = self::normalizeCityNameForListing($cityName, $stateName, $country);

        $state = $stateName !== '' ? State::firstOrCreate(
            ['country' => $country, 'slug' => Str::slug($stateName)],
            ['name' => $stateName, 'status' => true]
        ) : null;

        $city = $cityName !== '' ? City::firstOrCreate(
            ['state_id' => $state?->id, 'slug' => Str::slug($cityName)],
            ['name' => $cityName, 'state' => $stateName, 'country' => $country, 'status' => true]
        ) : null;

        if (empty($areaId) && $detectedArea !== '') {
            $detectedArea = self::clean($detectedArea);
            $detectedAreaNormalized = self::normalized($detectedArea);
            $cityIdForArea = $city?->id ?: null;
            if (! $cityIdForArea && self::pickScalar($request->input('city_id'))) {
                $cityIdForArea = (int) self::resolveCityIdForSnapshot(
                    $request->input('city_id'),
                    $cityName,
                    $stateName,
                    $country
                );
            }
            $existingArea = $cityIdForArea
                ? self::findExistingAreaForCity($cityIdForArea, $detectedAreaNormalized)
                : null;

            if ($existingArea && self::canAutoCreateAreasOnSave($request)) {
                Log::info('AreaListing: Used existing area: '.$existingArea->name.' instead of detected: '.$detectedArea);
                $areaId = $existingArea->id;
                $selectedArea = $existingArea;
                self::syncMasterAreaCityMetadata($selectedArea, $city, $state, $cityName, $stateName, $country);
            } elseif (empty($areaId)) {
                if (self::canAutoCreateAreasOnSave($request)) {
                    if ($existingArea) {
                        $areaId = $existingArea->id;
                        $selectedArea = $existingArea;
                        self::syncMasterAreaCityMetadata($selectedArea, $city, $state, $cityName, $stateName, $country);
                    } elseif ($cityIdForArea) {
                        $area = Area::firstOrCreate(
                            ['city_id' => $cityIdForArea, 'normalized_name' => $detectedAreaNormalized],
                            [
                                'state_id' => $state?->id,
                                'city' => $cityName,
                                'city_name' => $cityName,
                                'state' => $stateName,
                                'country' => $country,
                                'name' => $detectedArea,
                                'slug' => Str::slug($detectedArea),
                                'status' => true,
                                'workflow_status' => 'active',
                            ]
                        );
                        $areaId = $area->id;
                        $selectedArea = $area;
                        self::syncMasterAreaCityMetadata($selectedArea, $city, $state, $cityName, $stateName, $country);
                    }
                } elseif ($isUserPortal) {
                    if ($existingArea) {
                        $areaId = (int) $existingArea->id;
                        $selectedArea = $existingArea;
                        self::markPendingSuggestionsResolvedByMaster('area', $detectedAreaNormalized, null, $cityName);
                    } else {
                        self::storePendingSuggestion('area', [
                            'name' => $detectedArea,
                            'city' => $cityName,
                            'state' => $stateName,
                            'country' => $country,
                        ]);
                        $areaId = null;
                        $selectedArea = null;
                    }
                } elseif (! $existingArea) {
                    self::storePendingSuggestion('area', [
                        'name' => $detectedArea,
                        'city' => $cityName,
                        'state' => $stateName,
                        'country' => $country,
                    ]);
                }
            }
        }

        if (empty($subAreaId) && $detectedSubArea !== '') {
            $detectedSubArea = self::clean($detectedSubArea);
            $detectedSubNormalized = self::normalized($detectedSubArea);
            $targetAreaId = ! empty($areaId) ? (int) $areaId : null;

            if (! $targetAreaId && $detectedArea !== '') {
                $cityIdForSub = $city?->id ?: null;
                if (! $cityIdForSub && self::pickScalar($request->input('city_id'))) {
                    $cityIdForSub = (int) self::resolveCityIdForSnapshot(
                        $request->input('city_id'),
                        $cityName,
                        $stateName,
                        $country
                    );
                }
                if ($cityIdForSub) {
                    $existingAreaForSub = self::findExistingAreaForCity(
                        $cityIdForSub,
                        self::normalized(self::clean($detectedArea))
                    );
                    if ($existingAreaForSub) {
                        $targetAreaId = (int) $existingAreaForSub->id;
                        if (self::canAutoCreateAreasOnSave($request) || $isUserPortal) {
                            $areaId = $areaId ?: $targetAreaId;
                            $selectedArea = $selectedArea ?: $existingAreaForSub;
                        }
                    }
                }
            }

            $existingSubArea = $targetAreaId
                ? self::findExistingSubAreaForArea($targetAreaId, $detectedSubNormalized)
                : null;

            if ($existingSubArea && self::canAutoCreateAreasOnSave($request)) {
                Log::info('AreaListing: Used existing sub-area: '.$existingSubArea->name.' instead of detected: '.$detectedSubArea);
                $subAreaId = $existingSubArea->id;
                $selectedSubArea = $existingSubArea;
            } elseif ($targetAreaId || ($isUserPortal && $detectedSubArea !== '')) {
                if (self::canAutoCreateAreasOnSave($request)) {
                    if ($existingSubArea) {
                        $subAreaId = $existingSubArea->id;
                        $selectedSubArea = $existingSubArea;
                    } else {
                        $subArea = SubArea::firstOrCreate(
                            ['area_id' => $targetAreaId, 'normalized_name' => $detectedSubNormalized],
                            ['name' => $detectedSubArea, 'slug' => Str::slug($detectedSubArea), 'status' => true, 'workflow_status' => 'active']
                        );
                        $subAreaId = $subArea->id;
                        $selectedSubArea = $subArea;
                    }
                } elseif ($isUserPortal) {
                    if ($existingSubArea) {
                        $subAreaId = (int) $existingSubArea->id;
                        $selectedSubArea = $existingSubArea;
                        self::markPendingSuggestionsResolvedByMaster('sub_area', $detectedSubNormalized, $targetAreaId);
                    } else {
                        self::storePendingSuggestion('sub_area', [
                            'name' => $detectedSubArea,
                            'area_id' => $targetAreaId,
                        ]);
                    }
                } elseif (! $existingSubArea) {
                    self::storePendingSuggestion('sub_area', [
                        'name' => $detectedSubArea,
                        'area_id' => $targetAreaId,
                    ]);
                }
            } elseif (! self::canAutoCreateAreasOnSave($request) && ! $existingSubArea) {
                self::storePendingSuggestion('sub_area', [
                    'name' => $detectedSubArea,
                    'area_id' => null,
                    'city' => $detectedArea ?: $cityName,
                    'state' => $stateName,
                    'country' => $country,
                ]);
            }
        }

        if (empty($areaId) && empty($subAreaId) && $detectedArea === '' && $detectedSubArea === '' && $cityName === '' && $stateName === '') {
            return;
        }

        $area = ! empty($areaId) ? Area::find($areaId) : null;
        $subArea = ! empty($subAreaId) ? SubArea::find($subAreaId) : null;
        if ($area && $city) {
            self::syncMasterAreaCityMetadata($area, $city, $state, $cityName, $stateName, $country);
        }
        $areaName = $area?->name ?: $detectedArea;
        $subAreaName = $subArea?->name ?: $detectedSubArea;
        if ($isUserPortal) {
            if ($detectedArea !== '') {
                $areaName = $detectedArea;
            }
            if ($detectedSubArea !== '') {
                $subAreaName = $detectedSubArea;
            }
            if (empty($areaId)) {
                $areaId = null;
            }
            if (empty($subAreaId)) {
                $subAreaId = null;
            }
        }
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
            'manual_address' => self::pickManualAddressFromRequest($request) ?: null,
            'display_address' => $displayAddress ?: null,
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'location_source' => $request->input('area_listing_source', 'manual'),
            'source' => $request->input('area_listing_source', 'manual'),
            'is_verified' => $request->boolean('location_is_verified', false),
            'updated_by' => self::resolveAreaListingActorId(),
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

        if ($isUserPortal) {
            self::reconcilePendingSuggestionsAfterUserPortalSave((object) $locationData);
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

    private static function applyLocationNameMatch($query, string $nameColumn, string $detectedColumn, string $name, string $normalized): void
    {
        $query->where(function ($names) use ($nameColumn, $detectedColumn, $name, $normalized) {
            $names->where($nameColumn, $name)
                ->orWhere($detectedColumn, $name)
                ->orWhereRaw('LOWER(TRIM('.$nameColumn.')) = ?', [$normalized])
                ->orWhereRaw('LOWER(TRIM('.$detectedColumn.')) = ?', [$normalized]);
        });
    }

    private static function applyLocationAreaFilter($query, $areaId, ?string $areaName, ?string $areaNormalized): void
    {
        if (empty($areaId) && ($areaName === null || $areaNormalized === null || $areaNormalized === '')) {
            return;
        }

        $query->where(function ($areaQ) use ($areaId, $areaName, $areaNormalized) {
            if (! empty($areaId)) {
                $areaQ->where('area_id', $areaId);
            }
            if ($areaName !== null && $areaNormalized !== null && $areaNormalized !== '') {
                $areaQ->orWhere(function ($pending) use ($areaName, $areaNormalized) {
                    $pending->whereNull('area_id');
                    self::applyLocationNameMatch($pending, 'area_name', 'detected_area_name', $areaName, $areaNormalized);
                });
            }
        });
    }

    private static function applyLocationSubAreaFilter($query, $subAreaId, ?string $subAreaName, ?string $subAreaNormalized): void
    {
        if (empty($subAreaId) && ($subAreaName === null || $subAreaNormalized === null || $subAreaNormalized === '')) {
            return;
        }

        $query->where(function ($subQ) use ($subAreaId, $subAreaName, $subAreaNormalized) {
            if (! empty($subAreaId)) {
                $subQ->where('sub_area_id', $subAreaId);
            }
            if ($subAreaName !== null && $subAreaNormalized !== null && $subAreaNormalized !== '') {
                $subQ->orWhere(function ($pending) use ($subAreaName, $subAreaNormalized) {
                    $pending->whereNull('sub_area_id');
                    self::applyLocationNameMatch($pending, 'sub_area_name', 'detected_sub_area_name', $subAreaName, $subAreaNormalized);
                });
            }
        });
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

        $areaNameFilter = self::clean(self::pickString(
            data_get($filters, 'area_name'),
            data_get($filters, 'detected_area_name'),
            data_get($filters, 'location.area_name'),
            data_get($filters, 'location.detected_area_name'),
            $request->input('area_name')
        ));
        $subAreaNameFilter = self::clean(self::pickString(
            data_get($filters, 'sub_area_name'),
            data_get($filters, 'detected_sub_area_name'),
            data_get($filters, 'location.sub_area_name'),
            data_get($filters, 'location.detected_sub_area_name'),
            $request->input('sub_area_name')
        ));

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

        if (! empty($areaId) && (empty($cityId) || empty($stateId))) {
            $areaRowForIds = Area::find($areaId);
            if ($areaRowForIds) {
                if (empty($cityId) && ! empty($areaRowForIds->city_id)) {
                    $cityId = $areaRowForIds->city_id;
                }
                if (empty($stateId) && ! empty($areaRowForIds->state_id)) {
                    $stateId = $areaRowForIds->state_id;
                }
            }
        }

        if (empty($cityId)) {
            $cityName = self::clean(self::pickString(
                data_get($filters, 'location.city'),
                data_get($filters, 'city'),
                $request->input('city')
            ));
            $stateName = self::clean(self::pickString(
                data_get($filters, 'location.state'),
                data_get($filters, 'state'),
                $request->input('state')
            ));
            $countryName = self::clean(self::pickString(
                data_get($filters, 'location.country'),
                data_get($filters, 'country'),
                $request->input('country')
            ));
            if ($cityName !== '') {
                $cityIds = self::matchingCityIds($cityName, $stateName, $countryName);
                if ($cityIds->count() === 1) {
                    $cityId = $cityIds->first();
                    if (empty($stateId)) {
                        $stateId = City::find($cityId)?->state_id;
                    }
                }
            }
        }

        $cityIdForResolve = $cityId ? (int) $cityId : null;
        if (empty($areaId) && $areaNameFilter !== '' && $cityIdForResolve) {
            $resolvedArea = self::findExistingAreaForCity($cityIdForResolve, self::normalized($areaNameFilter));
            if ($resolvedArea) {
                $areaId = $resolvedArea->id;
            }
        }

        if (empty($subAreaId) && $subAreaNameFilter !== '') {
            $resolveAreaId = $areaId ? (int) $areaId : null;
            if (! $resolveAreaId && $areaNameFilter !== '' && $cityIdForResolve) {
                $resolvedArea = self::findExistingAreaForCity($cityIdForResolve, self::normalized($areaNameFilter));
                $resolveAreaId = $resolvedArea?->id;
            }
            if ($resolveAreaId) {
                $resolvedSub = self::findExistingSubAreaForArea((int) $resolveAreaId, self::normalized($subAreaNameFilter));
                if ($resolvedSub) {
                    $subAreaId = $resolvedSub->id;
                }
            }
        }

        $areaMatchName = null;
        $areaMatchNormalized = null;
        if (! empty($areaId)) {
            $areaRow = Area::find($areaId);
            if ($areaRow) {
                $areaMatchName = $areaRow->name;
                $areaMatchNormalized = $areaRow->normalized_name ?: self::normalized($areaRow->name);
            }
        } elseif ($areaNameFilter !== '') {
            $areaMatchName = $areaNameFilter;
            $areaMatchNormalized = self::normalized($areaNameFilter);
        }

        $subMatchName = null;
        $subMatchNormalized = null;
        if (! empty($subAreaId)) {
            $subRow = SubArea::find($subAreaId);
            if ($subRow) {
                $subMatchName = $subRow->name;
                $subMatchNormalized = $subRow->normalized_name ?: self::normalized($subRow->name);
            }
        } elseif ($subAreaNameFilter !== '') {
            $subMatchName = $subAreaNameFilter;
            $subMatchNormalized = self::normalized($subAreaNameFilter);
        }

        $hasAreaFilter = ! empty($areaId) || ($areaMatchNormalized !== null && $areaMatchNormalized !== '');
        $hasSubFilter = ! empty($subAreaId) || ($subMatchNormalized !== null && $subMatchNormalized !== '');

        if (empty($stateId) && empty($cityId) && ! $hasAreaFilter && ! $hasSubFilter) {
            return $query;
        }

        return $query->whereIn($listingColumn, function ($subQuery) use (
            $table,
            $foreignKey,
            $stateId,
            $cityId,
            $areaId,
            $subAreaId,
            $areaMatchName,
            $areaMatchNormalized,
            $subMatchName,
            $subMatchNormalized,
            $hasAreaFilter,
            $hasSubFilter
        ) {
            $subQuery->select($foreignKey)->from($table)
                ->when(! empty($stateId), fn ($q) => $q->where('state_id', $stateId))
                ->when(! empty($cityId), fn ($q) => $q->where('city_id', $cityId))
                ->when($hasAreaFilter, function ($q) use ($areaId, $areaMatchName, $areaMatchNormalized) {
                    self::applyLocationAreaFilter($q, $areaId, $areaMatchName, $areaMatchNormalized);
                })
                ->when($hasSubFilter, function ($q) use ($subAreaId, $subMatchName, $subMatchNormalized) {
                    self::applyLocationSubAreaFilter($q, $subAreaId, $subMatchName, $subMatchNormalized);
                });
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
            'detected_area_name' => $location?->detected_area_name ?? ($area ? null : ($location?->area_name ?? null)),
            'detected_sub_area_name' => $location?->detected_sub_area_name ?? ($subArea ? null : ($location?->sub_area_name ?? null)),
            'location_source' => $location?->location_source ?? $location?->source ?? 'manual',
            'is_verified' => (bool) ($location?->is_verified ?? false),
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
        $cityName = self::normalizeCityNameForListing($cityName, $stateName, $country);

        if (self::pickScalar($cityId)) {
            $existing = City::find((int) $cityId);
            if ($existing && $cityName !== '' && self::normalized($existing->name) !== self::normalized($cityName)) {
                $canonical = City::query()->where('name', $cityName);
                if ($stateName !== '') {
                    $canonical->where('state', $stateName);
                }
                if ($country !== '') {
                    $canonical->where('country', $country);
                }
                $canonicalCity = $canonical->first();
                if ($canonicalCity) {
                    return (int) $canonicalCity->id;
                }
            }

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

    /**
     * Map geocoded locality strings (e.g. "Baldev Nagar Barmer") to a managed city ("Barmer").
     */
    private static function normalizeCityNameForListing(string $cityName, string $stateName, string $country = 'India'): string
    {
        $cityName = self::clean($cityName);
        if ($cityName === '') {
            return '';
        }

        $query = City::query()->where('status', true);
        if ($stateName !== '') {
            $query->where('state', $stateName);
        }
        if ($country !== '') {
            $query->where('country', $country);
        }

        $normalized = self::normalized($cityName);
        $managed = (clone $query)->orderByRaw('LENGTH(name) ASC')->get(['id', 'name']);

        $exact = null;
        $embedded = null;
        foreach ($managed as $row) {
            $candidate = self::normalized($row->name);
            if ($candidate === '' || strlen($candidate) < 3) {
                continue;
            }
            if ($normalized === $candidate) {
                $exact = self::clean($row->name);
            }
            if (str_contains($normalized, $candidate)) {
                $embedded = self::clean($row->name);
                break;
            }
        }

        if ($embedded !== null && ($exact === null || strlen(self::normalized($embedded)) < strlen(self::normalized($exact)))) {
            return $embedded;
        }

        if ($exact !== null) {
            return $exact;
        }

        return $cityName;
    }

    /** Ensure auto-created master areas stay linked to the canonical city for admin filters. */
    private static function syncMasterAreaCityMetadata(
        ?Area $area,
        ?City $city,
        ?State $state,
        string $cityName,
        string $stateName,
        string $country
    ): void {
        if (! $area || ! $city) {
            return;
        }

        $canonicalCity = self::clean($cityName !== '' ? $cityName : $city->name);
        $needsUpdate = empty($area->city_id)
            || (int) $area->city_id !== (int) $city->id
            || self::normalized($area->city_name ?? $area->city ?? '') !== self::normalized($canonicalCity);

        if (! $needsUpdate) {
            return;
        }

        $area->update([
            'city_id' => $city->id,
            'state_id' => $state?->id ?: $city->state_id ?: $area->state_id,
            'city' => $canonicalCity,
            'city_name' => $canonicalCity,
            'state' => $stateName !== '' ? $stateName : ($state?->name ?: $area->state),
            'country' => $country !== '' ? $country : ($city->country ?: $area->country),
        ]);
    }

    /**
     * Repair master areas missing city_id or bound to a non-canonical geocoded city name.
     */
    public static function repairOrphanMasterAreas(): int
    {
        $repaired = 0;

        foreach (Area::query()->cursor() as $area) {
            $stateName = self::clean((string) ($area->state ?? ''));
            $country = self::clean((string) ($area->country ?? 'India')) ?: 'India';
            $rawCity = self::clean((string) ($area->city_name ?: $area->city ?: ''));
            if ($rawCity === '') {
                continue;
            }

            $canonicalCity = self::normalizeCityNameForListing($rawCity, $stateName, $country);
            $cityId = self::resolveCityIdForSnapshot(null, $canonicalCity, $stateName, $country);
            if (! $cityId) {
                continue;
            }

            $alreadyLinked = ! empty($area->city_id)
                && (int) $area->city_id === (int) $cityId
                && self::normalized($area->city_name ?? $area->city ?? '') === self::normalized($canonicalCity);

            if ($alreadyLinked) {
                continue;
            }

            $city = City::find($cityId);
            $state = $city?->state_id ? State::find($city->state_id) : null;
            self::syncMasterAreaCityMetadata($area, $city, $state, $canonicalCity, $stateName, $country);
            $repaired++;
        }

        return $repaired;
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

    private static function isSimilarName(string $left, ?string $right): bool
    {
        $right = (string) $right;
        if ($left === '' || $right === '' || $left === $right) {
            return false;
        }

        if (abs(strlen($left) - strlen($right)) > 4) {
            return false;
        }

        similar_text($left, $right, $percent);

        return $percent >= 84 || levenshtein($left, $right) <= 2;
    }

    private static function findExistingAreaForCity(?int $cityId, string $normalized): ?Area
    {
        if (! $cityId || $normalized === '') {
            return null;
        }

        $exact = Area::query()
            ->active()
            ->where('city_id', $cityId)
            ->where('normalized_name', $normalized)
            ->first();

        if ($exact) {
            return $exact;
        }

        return self::findSimilarAreaForCity($cityId, $normalized);
    }

    private static function findExistingSubAreaForArea(int $areaId, string $normalized): ?SubArea
    {
        if ($areaId <= 0 || $normalized === '') {
            return null;
        }

        $exact = SubArea::query()
            ->active()
            ->where('area_id', $areaId)
            ->where('normalized_name', $normalized)
            ->first();

        if ($exact) {
            return $exact;
        }

        return self::findSimilarSubAreaForArea($areaId, $normalized);
    }

    private static function findSimilarAreaForCity(?int $cityId, string $normalized): ?Area
    {
        if (! $cityId || $normalized === '') {
            return null;
        }

        return Area::query()
            ->active()
            ->where('city_id', $cityId)
            ->get()
            ->first(fn (Area $area) => self::isSimilarName($normalized, $area->normalized_name));
    }

    private static function findSimilarSubAreaForArea(int $areaId, string $normalized): ?SubArea
    {
        if ($areaId <= 0 || $normalized === '') {
            return null;
        }

        return SubArea::query()
            ->active()
            ->where('area_id', $areaId)
            ->get()
            ->first(fn (SubArea $subArea) => self::isSimilarName($normalized, $subArea->normalized_name));
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
                foreach ($area->subAreas as $subArea) {
                    if ($normalized === self::normalized($subArea->name)) {
                        return self::formatResolvedLocation($area, $subArea, 'name', null, $rawName);
                    }
                }
            }
        }

        foreach ($locationNames as $normalized => $rawName) {
            foreach ($areas as $area) {
                if ($normalized !== self::normalized($area->name)) {
                    continue;
                }

                foreach ($locationNames as $subNormalized => $subRawName) {
                    foreach ($area->subAreas as $subArea) {
                        if ($subNormalized === self::normalized($subArea->name)) {
                            return self::formatResolvedLocation($area, $subArea, 'name', null, $subRawName);
                        }
                    }
                }

                return self::formatResolvedLocation($area, null, 'name', null, $rawName);
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
