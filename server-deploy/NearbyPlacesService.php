<?php

namespace App\Plugins\NearbyPlaces\Services;

use App\Models\Property;
use App\Plugins\NearbyPlaces\Models\NearbyCategory;
use App\Plugins\NearbyPlaces\Models\NearbyPlaceCache;
use App\Plugins\NearbyPlaces\Support\NearbyPlacesSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NearbyPlacesService
{
    public function __construct(
        protected GoogleNearbySearchService $google,
        protected GoogleDistanceMatrixService $distanceMatrix,
    ) {
    }

    protected function computeTravelTimeEnabledFlag(): bool
    {
        return NearbyPlacesSettings::travelTimeEnabled()
            && $this->google->hasApiKey()
            && $this->distanceMatrix->hasApiKey();
    }

    public function getForProperty(int $propertyId, bool $forceRefresh = false): array
    {
        if (!NearbyPlacesSettings::isEnabled()) {
            return [
                'enabled' => false,
                'travel_time_enabled' => false,
                'area_label' => '',
                'categories' => [],
                'places' => [],
            ];
        }

        $property = Property::query()->find($propertyId);
        if (!$property) {
            return ['enabled' => false, 'travel_time_enabled' => false, 'error' => 'Property not found'];
        }

        $latitude = (float) $property->latitude;
        $longitude = (float) $property->longitude;

        if (!$latitude || !$longitude) {
            return [
                'enabled' => true,
                'travel_time_enabled' => false,
                'area_label' => $this->buildAreaLabel($property),
                'categories' => $this->formatCategories(),
                'places' => [],
                'message' => 'Property coordinates are unavailable.',
            ];
        }

        $categories = NearbyCategory::query()->active()->orderBy('sort_order')->get();
        if ($categories->isEmpty()) {
            return [
                'enabled' => true,
                'travel_time_enabled' => false,
                'area_label' => $this->buildAreaLabel($property),
                'categories' => [],
                'places' => [],
            ];
        }

        if ($forceRefresh) {
            $this->clearCacheForProperty($propertyId);
        }

        $places = [];
        $stopsIndex = [];

        foreach ($categories as $category) {
            $cached = $this->getCachedPlaces($propertyId, $category->id);
            $needsFetch = $cached->isEmpty() || $cached->contains(fn ($row) => !$row->expires_at || $row->expires_at->isPast());

            if ($needsFetch && $category->google_place_type) {
                $this->fetchAndStore($property, $category, $latitude, $longitude);
                $cached = $this->getCachedPlaces($propertyId, $category->id);
            }

            foreach ($cached as $row) {
                $pid = (string) ($row->google_place_id ?? '');
                if ($pid !== '') {
                    $stopsIndex[$pid] = $row;
                }
            }

            $places[$category->slug] = $cached->map(function (NearbyPlaceCache $row) use ($latitude, $longitude) {
                return $this->formatPlaceRow($row, $latitude, $longitude);
            })->values()->all();
        }

        $travelFlag = $this->computeTravelTimeEnabledFlag();

        $travelByPlaceId = [];
        if ($travelFlag && !empty($stopsIndex)) {
            $travelByPlaceId = $this->resolveTravelTimesForStops($property->id, $latitude, $longitude, $stopsIndex);
            $places = $this->mergeTravelIntoPlaces($places, $travelByPlaceId);
        }

        return [
            'enabled' => true,
            'travel_time_enabled' => $travelFlag,
            'property_id' => $property->id,
            'area_label' => $this->buildAreaLabel($property),
            'categories' => $this->formatCategories(),
            'places' => $places,
        ];
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $places
     * @param  array<string, array{duration_seconds: int, duration_text: string}>  $travelByPlaceId
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function mergeTravelIntoPlaces(array $places, array $travelByPlaceId): array
    {
        foreach ($places as $slug => $items) {
            foreach ($items as $i => $item) {
                $pid = (string) ($item['google_place_id'] ?? '');
                if ($pid !== '' && isset($travelByPlaceId[$pid])) {
                    $places[$slug][$i]['travel_duration_seconds'] = $travelByPlaceId[$pid]['duration_seconds'];
                    $places[$slug][$i]['travel_duration_text'] = $travelByPlaceId[$pid]['duration_text'];
                }
            }
        }

        return $places;
    }

    /**
     * @param  array<string, NearbyPlaceCache>  $stopsIndex
     * @return array<string, array{duration_seconds: int, duration_text: string}>
     */
    protected function resolveTravelTimesForStops(int $propertyId, float $originLat, float $originLng, array $stopsIndex): array
    {
        $ids = array_keys($stopsIndex);
        if ($ids === []) {
            return [];
        }

        $cachedRows = DB::table('travel_time_cache')
            ->where('property_id', $propertyId)
            ->whereIn('google_place_id', $ids)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->get()
            ->keyBy('google_place_id');

        $result = [];
        foreach ($cachedRows as $pid => $row) {
            $result[(string) $pid] = [
                'duration_seconds' => (int) $row->duration_seconds,
                'duration_text' => (string) $row->duration_text,
            ];
        }

        $missing = [];
        foreach ($ids as $pid) {
            if (!isset($result[$pid])) {
                $missing[$pid] = $stopsIndex[$pid];
            }
        }

        if ($missing !== []) {
            $ttlHours = NearbyPlacesSettings::travelCacheTtlHours();
            $expiresAt = now()->addHours($ttlHours);
            $fetchedAt = now();

            $items = [];
            foreach ($missing as $pid => $row) {
                $items[] = [
                    'place_id' => $pid,
                    'lat' => (float) $row->latitude,
                    'lng' => (float) $row->longitude,
                ];
            }

            foreach (array_chunk($items, 25) as $chunk) {
                $batch = $this->distanceMatrix->fetchDrivingDurations($originLat, $originLng, $chunk);
                foreach ($batch as $pid => $data) {
                    $result[$pid] = $data;

                    $now = now();
                    $existingId = DB::table('travel_time_cache')
                        ->where('property_id', $propertyId)
                        ->where('google_place_id', $pid)
                        ->value('id');

                    $payload = [
                        'property_id' => $propertyId,
                        'google_place_id' => $pid,
                        'duration_seconds' => $data['duration_seconds'],
                        'duration_text' => $data['duration_text'],
                        'fetched_at' => $fetchedAt,
                        'expires_at' => $expiresAt,
                        'updated_at' => $now,
                    ];

                    if ($existingId) {
                        DB::table('travel_time_cache')->where('id', $existingId)->update($payload);
                    } else {
                        $payload['created_at'] = $now;
                        DB::table('travel_time_cache')->insert($payload);
                    }
                }
            }
        }

        return $result;
    }

    protected function getCachedPlaces(int $propertyId, int $categoryId)
    {
        return NearbyPlaceCache::query()
            ->where('property_id', $propertyId)
            ->where('nearby_category_id', $categoryId)
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderBy('distance_m')
            ->get();
    }

    protected function fetchAndStore(Property $property, NearbyCategory $category, float $latitude, float $longitude): void
    {
        $radius = max(100, (int) ($category->default_radius_m ?: NearbyPlacesSettings::defaultRadiusM()));
        $maxResults = max(1, min(20, (int) ($category->max_results ?: NearbyPlacesSettings::defaultMaxResults())));

        NearbyPlaceCache::query()
            ->where('property_id', $property->id)
            ->where('nearby_category_id', $category->id)
            ->delete();

        $results = $this->google->search($latitude, $longitude, $radius, (string) $category->google_place_type, $maxResults);
        if (empty($results)) {
            return;
        }

        $rows = [];
        $expiresAt = now()->addHours(NearbyPlacesSettings::cacheTtlHours());
        $fetchedAt = now();

        foreach ($results as $result) {
            $placeLat = (float) ($result['geometry']['location']['lat'] ?? 0);
            $placeLng = (float) ($result['geometry']['location']['lng'] ?? 0);
            $distanceM = $this->distanceMeters($latitude, $longitude, $placeLat, $placeLng);

            $rows[] = [
                'property_id' => $property->id,
                'nearby_category_id' => $category->id,
                'google_place_id' => (string) ($result['place_id'] ?? ''),
                'name' => (string) ($result['name'] ?? 'Unnamed place'),
                'rating' => isset($result['rating']) ? (float) $result['rating'] : null,
                'distance_m' => $distanceM,
                'distance_text' => $this->formatDistanceText($distanceM),
                'is_open' => isset($result['opening_hours']['open_now']) ? (bool) $result['opening_hours']['open_now'] : null,
                'latitude' => $placeLat,
                'longitude' => $placeLng,
                'vicinity' => isset($result['vicinity']) ? (string) $result['vicinity'] : null,
                'place_types' => json_encode($result['types'] ?? []),
                'fetched_at' => $fetchedAt,
                'expires_at' => $expiresAt,
                'created_at' => $fetchedAt,
                'updated_at' => $fetchedAt,
            ];
        }

        usort($rows, fn ($a, $b) => $a['distance_m'] <=> $b['distance_m']);
        $rows = array_slice($rows, 0, $maxResults);

        if (!empty($rows)) {
            DB::table('nearby_place_cache')->insert($rows);
        }
    }

    protected function formatCategories(): array
    {
        return NearbyCategory::query()
            ->active()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (NearbyCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'icon' => $category->icon,
            ])
            ->values()
            ->all();
    }

    protected function formatPlaceRow(NearbyPlaceCache $row, float $originLat, float $originLng): array
    {
        return [
            'category_id' => $row->nearby_category_id,
            'name' => $row->name,
            'distance_m' => $row->distance_m,
            'distance_text' => $row->distance_text ?: $this->formatDistanceText((int) $row->distance_m),
            'rating' => $row->rating,
            'is_open' => $row->is_open,
            'google_place_id' => $row->google_place_id,
            'directions_url' => $this->buildDirectionsUrl($originLat, $originLng, (float) $row->latitude, (float) $row->longitude, $row->google_place_id),
            'vicinity' => $row->vicinity,
        ];
    }

    public function buildDirectionsUrl(float $originLat, float $originLng, float $destLat, float $destLng, ?string $placeId = null): string
    {
        $params = [
            'api' => 1,
            'origin' => $originLat . ',' . $originLng,
        ];

        if ($placeId) {
            $params['destination_place_id'] = $placeId;
        } else {
            $params['destination'] = $destLat . ',' . $destLng;
        }

        return 'https://www.google.com/maps/dir/?' . http_build_query($params);
    }

    /**
     * Public label for Nearby section — sub-area / area names from Area Wise only,
     * plus city/state from property or listing snapshot. Never uses full/manual address.
     */
    public function buildAreaLabel(Property $property): string
    {
        $ctx = $this->resolveSafeAreaContext($property);

        if ($ctx['sub_area_name'] !== '' && $ctx['area_name'] !== '') {
            return $ctx['sub_area_name'] . ', ' . $ctx['area_name'];
        }

        if ($ctx['area_name'] !== '' && $ctx['city'] !== '') {
            return $ctx['area_name'] . ', ' . $ctx['city'];
        }

        if ($ctx['city'] !== '' && $ctx['state'] !== '') {
            return $ctx['city'] . ', ' . $ctx['state'];
        }

        $fallback = array_filter([
            $ctx['area_name'] !== '' ? $ctx['area_name'] : null,
            $ctx['city'] !== '' ? $ctx['city'] : null,
            $ctx['state'] !== '' ? $ctx['state'] : null,
        ]);

        if (!empty($fallback)) {
            return implode(', ', $fallback);
        }

        return 'this property';
    }

    /**
     * @return array{sub_area_name:string,area_name:string,city:string,state:string}
     */
    protected function resolveSafeAreaContext(Property $property): array
    {
        $city = $this->cleanLocationPart($property->city ?? '');
        $state = $this->cleanLocationPart($property->state ?? '');

        if (!Schema::hasTable('area_listing_property_locations')) {
            return [
                'sub_area_name' => '',
                'area_name' => '',
                'city' => $city,
                'state' => $state,
            ];
        }

        $location = DB::table('area_listing_property_locations')->where('property_id', $property->id)->first();
        if (!$location) {
            return [
                'sub_area_name' => '',
                'area_name' => '',
                'city' => $city,
                'state' => $state,
            ];
        }

        $areaModel = null;
        $subAreaModel = null;

        if (!empty($location->area_id) && class_exists(\App\Plugins\AreaListing\Models\Area::class)) {
            $areaModel = \App\Plugins\AreaListing\Models\Area::find($location->area_id);
        }

        if (!empty($location->sub_area_id) && class_exists(\App\Plugins\AreaListing\Models\SubArea::class)) {
            $subAreaModel = \App\Plugins\AreaListing\Models\SubArea::find($location->sub_area_id);
        }

        $areaName = $this->cleanLocationPart(
            ($areaModel?->name ?? null)
                ?? ($location->area_name ?? null)
                ?? ($location->detected_area_name ?? null)
                ?? ''
        );

        $subAreaName = $this->cleanLocationPart(
            ($subAreaModel?->name ?? null)
                ?? ($location->sub_area_name ?? null)
                ?? ($location->detected_sub_area_name ?? null)
                ?? ''
        );

        $listingCity = $this->cleanLocationPart($location->city ?? '');
        if ($listingCity !== '') {
            $city = $city !== '' ? $city : $listingCity;
        }

        if ($areaModel) {
            $derivedCity = $this->cleanLocationPart(($areaModel->city_name ?: $areaModel->city) ?? '');
            if ($derivedCity !== '') {
                $city = $city !== '' ? $city : $derivedCity;
            }

            $derivedState = $this->cleanLocationPart($areaModel->state ?? '');
            if ($derivedState !== '') {
                $state = $state !== '' ? $state : $derivedState;
            }
        }

        return [
            'sub_area_name' => $subAreaName,
            'area_name' => $areaName,
            'city' => $city,
            'state' => $state,
        ];
    }

    protected function cleanLocationPart(?string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $value;
    }

    public function formatDistanceText(int $meters): string
    {
        if ($meters < 1000) {
            return $meters . ' m';
        }

        return rtrim(rtrim(number_format($meters / 1000, 1, '.', ''), '0'), '.') . ' km';
    }

    public function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $earth = 6371000;
        $latFrom = deg2rad($lat1);
        $latTo = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2 + cos($latFrom) * cos($latTo) * sin($lonDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round($earth * $c);
    }

    public function clearCacheForProperty(int $propertyId): int
    {
        $deleted = NearbyPlaceCache::query()->where('property_id', $propertyId)->delete();

        try {
            DB::table('travel_time_cache')->where('property_id', $propertyId)->delete();
        } catch (\Throwable $e) {
            // Table may not exist before migration during transitional deploy.
        }

        return $deleted;
    }

    public function clearAllCache(): int
    {
        $deleted = NearbyPlaceCache::query()->delete();

        try {
            DB::table('travel_time_cache')->delete();
        } catch (\Throwable $e) {
            //
        }

        return $deleted;
    }
}
