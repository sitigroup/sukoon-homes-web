<?php

namespace App\Plugins\NearbyPlaces\Services;

use App\Models\Property;
use App\Plugins\NearbyPlaces\Models\NearbyCategory;
use App\Plugins\NearbyPlaces\Models\NearbyPlaceCache;
use App\Plugins\NearbyPlaces\Models\NearbyPlaceOverride;
use App\Plugins\NearbyPlaces\Support\NearbyPlacesSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NearbyPlacesService
{
    public function __construct(
        protected GoogleNearbySearchService $google,
        protected GoogleDistanceMatrixService $distanceMatrix,
        protected NearbyPlaceQualityFilter $qualityFilter,
        protected NearbyPlaceOverrideService $overrideService,
    ) {
    }

    protected function computeTravelTimeEnabledFlag(): bool
    {
        return NearbyPlacesSettings::travelTimeEnabled()
            && $this->google->hasApiKey()
            && $this->distanceMatrix->hasApiKey();
    }

    public function getForProperty(int $propertyId, bool $forceRefresh = false, bool $adminPreview = false, ?int $categoryId = null): array
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

        $origin = $this->resolveSearchCoordinates($property);
        $latitude = $origin['lat'];
        $longitude = $origin['lng'];

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
            if ($categoryId !== null) {
                $this->clearCacheForPropertyCategory($propertyId, $categoryId);
            } else {
                $this->clearCacheForProperty($propertyId);
            }
        }

        $allOverrides = $this->overrideService->loadForProperty($propertyId);
        $places = [];
        $stopsIndex = [];
        $qualityPreview = [];

        foreach ($categories as $category) {
            $categoryPreview = [
                'category' => $category->name,
                'slug' => $category->slug,
                'before_count' => 0,
                'after_count' => 0,
                'shown' => [],
                'hidden' => [],
            ];

            $cached = $this->getCachedPlaces($propertyId, $category->id);
            $needsFetch = $cached->isEmpty() || $cached->contains(fn ($row) => !$row->expires_at || $row->expires_at->isPast());

            if ($needsFetch && $category->google_place_type) {
                if ($adminPreview) {
                    $this->fetchAndStore($property, $category, $latitude, $longitude, $categoryPreview);
                } else {
                    $this->fetchAndStore($property, $category, $latitude, $longitude);
                }
                $cached = $this->getCachedPlaces($propertyId, $category->id);
            } elseif ($adminPreview) {
                $categoryPreview['before_count'] = $cached->count();
            }

            $formatted = [];
            if ($adminPreview) {
                $categoryPreview['shown'] = [];
            }

            foreach ($cached as $row) {
                $evaluation = NearbyPlacesSettings::qualityFilterEnabled()
                    ? $this->qualityFilter->evaluateRow($row, $category, $latitude, $longitude)
                    : ['pass' => true, 'hide_reason' => null, 'quality_score' => 0, 'quality_flags' => []];

                $normalizedName = $this->overrideService->normalizeName((string) $row->name);
                $matching = $this->overrideService->matchingOverrides(
                    $allOverrides,
                    (string) $row->google_place_id,
                    $normalizedName,
                    $propertyId,
                    (int) $category->id
                );
                $resolved = $this->overrideService->resolveVisibility($evaluation, $matching);

                if (!$resolved['visible']) {
                    if ($adminPreview) {
                        $categoryPreview['hidden'][] = [
                            'name' => $row->name,
                            'google_place_id' => $row->google_place_id,
                            'reason' => $resolved['hidden_reason'] ?? $evaluation['hide_reason'],
                            'quality_flags' => $evaluation['quality_flags'],
                            'visibility_status' => $resolved['status'],
                        ];
                    }

                    continue;
                }

                $primaryOverride = $this->pickPrimaryOverride($matching, $resolved['override']);
                $formattedRow = $this->formatPlaceRow($row, $latitude, $longitude, $evaluation, $primaryOverride);
                if (!$formattedRow) {
                    continue;
                }

                $formatted[] = $formattedRow;

                $pid = (string) ($row->google_place_id ?? '');
                if ($pid !== '') {
                    $stopsIndex[$pid] = $row;
                }

                if ($adminPreview) {
                    $categoryPreview['shown'][] = [
                        'name' => $formattedRow['name'],
                        'google_place_id' => $formattedRow['google_place_id'],
                        'quality_score' => $formattedRow['quality_score'],
                        'quality_flags' => $formattedRow['quality_flags'],
                        'distance_text' => $formattedRow['distance_text'],
                        'visibility_status' => $resolved['status'],
                    ];
                }
            }

            $manualRows = $this->overrideService->buildManualPlaceRows(
                $allOverrides,
                $propertyId,
                (int) $category->id,
                $latitude,
                $longitude
            );
            foreach ($manualRows as $manualRow) {
                $formatted[] = $this->overrideService->stripAdminFields($manualRow);
            }

            $formatted = $this->overrideService->sortPlaces($formatted);
            $maxAfterFilter = max(1, min(20, (int) ($category->max_results_after_filter ?: $category->max_results ?: 5)));
            $formatted = array_slice($formatted, 0, $maxAfterFilter);
            $places[$category->slug] = array_map(
                fn (array $row) => $this->overrideService->stripAdminFields($row),
                $formatted
            );

            if ($adminPreview) {
                $categoryPreview['after_count'] = count($formatted);
                $qualityPreview[$category->slug] = $categoryPreview;
            }
        }

        $travelFlag = $this->computeTravelTimeEnabledFlag();

        $travelByPlaceId = [];
        if ($travelFlag && !empty($stopsIndex)) {
            $travelByPlaceId = $this->resolveTravelTimesForStops($property->id, $latitude, $longitude, $stopsIndex);
            $places = $this->mergeTravelIntoPlaces($places, $travelByPlaceId);
        }

        $payload = [
            'enabled' => true,
            'travel_time_enabled' => $travelFlag,
            'quality_filter_enabled' => NearbyPlacesSettings::qualityFilterEnabled(),
            'property_id' => $property->id,
            'area_label' => $this->buildAreaLabel($property),
            'categories' => $this->formatCategories(),
            'places' => $places,
        ];

        if ($adminPreview) {
            $payload['quality_preview'] = $qualityPreview;
        }

        return $payload;
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

    protected function resolveCategoryCacheTtlHours(NearbyCategory $category): int
    {
        return $category->effectiveCacheTtlHours();
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

    protected function fetchAndStore(
        Property $property,
        NearbyCategory $category,
        float $latitude,
        float $longitude,
        ?array &$fetchPreview = null,
    ): void {
        $radius = max(100, (int) ($category->default_radius_m ?: NearbyPlacesSettings::defaultRadiusM()));
        $maxResults = max(1, min(20, (int) ($category->max_results ?: NearbyPlacesSettings::defaultMaxResults())));
        $maxAfterFilter = max(1, min(20, (int) ($category->max_results_after_filter ?: $maxResults)));
        $fetchLimit = min(20, max($maxAfterFilter * 3, $maxAfterFilter + 5));

        NearbyPlaceCache::query()
            ->where('property_id', $property->id)
            ->where('nearby_category_id', $category->id)
            ->delete();

        $results = $this->google->search($latitude, $longitude, $radius, (string) $category->google_place_type, $fetchLimit);
        if (empty($results)) {
            if ($fetchPreview !== null) {
                $fetchPreview['before_count'] = 0;
                $fetchPreview['after_count'] = 0;
                $fetchPreview['shown'] = [];
                $fetchPreview['hidden'] = [];
            }

            return;
        }

        $expiresAt = now()->addHours($this->resolveCategoryCacheTtlHours($category));
        $fetchedAt = now();
        $candidates = [];
        $hidden = [];

        foreach ($results as $result) {
            $placeLat = (float) ($result['geometry']['location']['lat'] ?? 0);
            $placeLng = (float) ($result['geometry']['location']['lng'] ?? 0);
            $distanceM = $this->distanceMeters($latitude, $longitude, $placeLat, $placeLng);

            $candidate = [
                'property_id' => $property->id,
                'nearby_category_id' => $category->id,
                'google_place_id' => (string) ($result['place_id'] ?? ''),
                'name' => (string) ($result['name'] ?? 'Unnamed place'),
                'rating' => isset($result['rating']) ? (float) $result['rating'] : null,
                'user_ratings_total' => isset($result['user_ratings_total']) ? (int) $result['user_ratings_total'] : null,
                'business_status' => isset($result['business_status']) ? (string) $result['business_status'] : null,
                'distance_m' => $distanceM,
                'distance_text' => $this->formatDistanceDisplayLabel($distanceM),
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

            $evaluation = NearbyPlacesSettings::qualityFilterEnabled()
                ? $this->qualityFilter->evaluateCandidate($candidate, $category, $latitude, $longitude)
                : ['pass' => true, 'hide_reason' => null, 'quality_score' => 0, 'quality_flags' => []];

            if (!$evaluation['pass']) {
                if ($fetchPreview !== null) {
                    $hidden[] = [
                        'name' => $candidate['name'],
                        'google_place_id' => $candidate['google_place_id'],
                        'reason' => $evaluation['hide_reason'],
                        'quality_flags' => $evaluation['quality_flags'],
                    ];
                }
            }

            $candidate['quality_passed'] = $evaluation['pass'] ? 1 : 0;
            $candidate['hidden_reason'] = $evaluation['hide_reason'];
            $candidate['_quality_score'] = $evaluation['quality_score'];
            $candidate['_quality_flags'] = $evaluation['quality_flags'];
            $candidates[] = $candidate;
        }

        $beforeFilterCount = count($results);
        $candidates = $this->qualityFilter->deduplicate($candidates);
        $candidates = $this->qualityFilter->sortByQualityScore($candidates);
        $rows = $candidates;

        foreach ($rows as &$row) {
            unset($row['_quality_score'], $row['_quality_flags']);
        }
        unset($row);

        if ($fetchPreview !== null) {
            $fetchPreview['before_count'] = $beforeFilterCount;
            $fetchPreview['after_count'] = count(array_filter($rows, fn ($r) => (int) ($r['quality_passed'] ?? 0) === 1));
            $fetchPreview['hidden'] = array_merge($fetchPreview['hidden'] ?? [], $hidden);
        }

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

    protected function formatPlaceRow(
        NearbyPlaceCache $row,
        float $originLat,
        float $originLng,
        ?array $evaluation = null,
        ?NearbyPlaceOverride $override = null,
    ): ?array {
        $placeTypes = $this->decodePlaceTypes($row->place_types);
        $destLat = $override?->manual_lat !== null ? (float) $override->manual_lat : (float) $row->latitude;
        $destLng = $override?->manual_lng !== null ? (float) $override->manual_lng : (float) $row->longitude;
        $cachedDistanceM = $row->distance_m === null ? null : (int) $row->distance_m;

        $ctx = $this->resolvePlaceDistanceContext(
            $originLat,
            $originLng,
            $destLat,
            $destLng,
            $cachedDistanceM,
            $placeTypes
        );

        $distanceText = $ctx['sameLocation']
            ? 'At this location'
            : $this->formatDistanceDisplayLabel($ctx['distanceM']);

        $directionsMeta = $ctx['sameLocation']
            ? ['url' => '', 'origin' => '', 'destination' => '']
            : $this->buildDirectionsUrlMeta(
                $originLat,
                $originLng,
                $destLat,
                $destLng,
                $row->google_place_id,
                ($override?->display_name ?: null) ?? $row->name ?: $row->vicinity
            );

        $navigation = $this->qualityFilter->evaluateNavigation(
            $originLat,
            $originLng,
            $destLat,
            $destLng,
            $placeTypes,
            $row->google_place_id,
            $override?->display_name ?: $row->name,
            $row->vicinity,
            $distanceText,
            $ctx['coordsIdentical'],
            ($directionsMeta['url'] ?? '') !== ''
        );

        if (!$navigation['show_directions'] || ($override && $override->disable_directions)) {
            $directionsMeta = ['url' => '', 'origin' => '', 'destination' => ''];
            $navigation['show_directions'] = false;
        }

        if ($override?->manual_direction_url) {
            $directionsMeta['url'] = $override->manual_direction_url;
            $navigation['show_directions'] = !$override->disable_directions;
        }

        $qualityScore = (float) ($evaluation['quality_score'] ?? 0);
        $qualityFlags = $evaluation['quality_flags'] ?? [];

        $rowData = [
            'category_id' => $row->nearby_category_id,
            'name' => $row->name,
            'distance_m' => $ctx['distanceM'],
            'distance_text' => $distanceText,
            'rating' => $row->rating,
            'review_count' => $row->user_ratings_total,
            'is_open' => $row->is_open,
            'google_place_id' => $row->google_place_id,
            'is_same_location' => $ctx['sameLocation'],
            'directions_url' => $directionsMeta['url'],
            'show_directions' => $navigation['show_directions'],
            'navigation_quality' => $navigation['navigation_quality'],
            'invalid_navigation' => $navigation['invalid_navigation'],
            'navigation_flags' => $navigation['navigation_flags'],
            'vicinity' => $row->vicinity,
            'place_types' => $placeTypes,
            'quality_score' => $qualityScore,
            'quality_flags' => $qualityFlags,
            'is_recommended' => false,
            'display_order' => null,
        ];

        return $this->overrideService->applyOverrideToPublicRow($rowData, $override);
    }

    /**
     * @return array{
     *   distanceM:?int,
     *   sameLocation:bool,
     *   isRealBusiness:bool,
     *   computedDistanceM:?int,
     *   cachedDistanceM:?int,
     *   coordsIdentical:bool
     * }
     */
    protected function resolvePlaceDistanceContext(
        float $originLat,
        float $originLng,
        float $destLat,
        float $destLng,
        ?int $cachedDistanceM,
        array $placeTypes,
    ): array {
        $isRealBusiness = $this->isRealBusiness($placeTypes);
        $computedDistanceM = null;

        if ($this->hasUsableCoordinates($originLat, $originLng) && $this->hasUsableCoordinates($destLat, $destLng)) {
            $computedDistanceM = $this->distanceMeters($originLat, $originLng, $destLat, $destLng);
        }

        $coordsIdentical = $this->coordinatesAreIdentical($originLat, $originLng, $destLat, $destLng);

        if ($isRealBusiness) {
            $distanceM = $computedDistanceM ?? (($cachedDistanceM !== null && $cachedDistanceM >= 20) ? $cachedDistanceM : null);

            return [
                'distanceM' => $distanceM,
                'sameLocation' => false,
                'isRealBusiness' => true,
                'computedDistanceM' => $computedDistanceM,
                'cachedDistanceM' => $cachedDistanceM,
                'coordsIdentical' => $coordsIdentical,
            ];
        }

        $distanceM = $computedDistanceM ?? $cachedDistanceM;
        $sameLocation = $coordsIdentical && ($distanceM === null || $distanceM < 20);

        return [
            'distanceM' => $distanceM,
            'sameLocation' => $sameLocation,
            'isRealBusiness' => false,
            'computedDistanceM' => $computedDistanceM,
            'cachedDistanceM' => $cachedDistanceM,
            'coordsIdentical' => $coordsIdentical,
        ];
    }

    protected function isRealBusiness(array $placeTypes): bool
    {
        $businessTypes = [
            'establishment',
            'restaurant',
            'food',
            'store',
            'hospital',
            'school',
            'gym',
            'bank',
            'cafe',
            'meal_takeaway',
            'meal_delivery',
            'pharmacy',
            'supermarket',
            'shopping_mall',
            'doctor',
            'health',
            'dentist',
            'lodging',
        ];

        foreach ($placeTypes as $type) {
            if (in_array($type, $businessTypes, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function decodePlaceTypes(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter(array_map('strval', $raw)));
        }

        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? array_values(array_filter(array_map('strval', $decoded))) : [];
    }

    protected function isSameLocation(float $originLat, float $originLng, float $destLat, float $destLng, ?int $distanceM, array $placeTypes = []): bool
    {
        if ($this->isRealBusiness($placeTypes)) {
            return false;
        }

        if (!$this->coordinatesAreIdentical($originLat, $originLng, $destLat, $destLng)) {
            return false;
        }

        return $distanceM === null || $distanceM < 20;
    }

    protected function coordinatesAreIdentical(float $lat1, float $lng1, float $lat2, float $lng2): bool
    {
        return abs($lat1 - $lat2) < 0.00005 && abs($lng1 - $lng2) < 0.00005;
    }

    /**
     * @return array{url:string, origin:string, destination:string}
     */
    protected function buildDirectionsUrlMeta(
        float $originLat,
        float $originLng,
        float $destLat,
        float $destLng,
        ?string $placeId = null,
        ?string $destinationLabel = null,
    ): array {
        $empty = ['url' => '', 'origin' => '', 'destination' => ''];

        if (!$this->hasUsableCoordinates($originLat, $originLng)) {
            return $empty;
        }

        $placeId = trim((string) ($placeId ?? ''));
        $label = trim((string) ($destinationLabel ?? ''));
        $origin = $originLat . ',' . $originLng;
        $hasDestCoords = $this->hasUsableCoordinates($destLat, $destLng);
        $coordsIdentical = $hasDestCoords
            && $this->coordinatesAreIdentical($originLat, $originLng, $destLat, $destLng);

        if ($hasDestCoords && !$coordsIdentical) {
            $destination = $destLat . ',' . $destLng;

            return [
                'url' => $this->buildGoogleDirectionsUrl($origin, $destination),
                'origin' => $origin,
                'destination' => $destination,
            ];
        }

        if ($placeId !== '' && $label !== '') {
            return [
                'url' => $this->buildGoogleDirectionsUrl($origin, $label, $placeId),
                'origin' => $origin,
                'destination' => $label,
            ];
        }

        return $empty;
    }

    protected function buildGoogleDirectionsUrl(string $origin, string $destination, ?string $placeId = null): string
    {
        $destinationParam = preg_match('/^-?\d+(?:\.\d+)?,-?\d+(?:\.\d+)?$/', $destination)
            ? $destination
            : rawurlencode($destination);

        $query = 'api=1'
            . '&origin=' . $origin
            . '&destination=' . $destinationParam;

        if ($placeId !== null && trim($placeId) !== '') {
            $query .= '&destination_place_id=' . rawurlencode(trim($placeId));
        }

        $query .= '&travelmode=driving';

        return 'https://www.google.com/maps/dir/?' . $query;
    }

    public function buildDirectionsUrl(
        float $originLat,
        float $originLng,
        float $destLat,
        float $destLng,
        ?string $placeId = null,
        ?string $destinationLabel = null,
    ): string {
        return $this->buildDirectionsUrlMeta(
            $originLat,
            $originLng,
            $destLat,
            $destLng,
            $placeId,
            $destinationLabel
        )['url'];
    }

    protected function hasUsableCoordinates(float $lat, float $lng): bool
    {
        if (!is_finite($lat) || !is_finite($lng)) {
            return false;
        }

        if (abs($lat) < 1e-7 && abs($lng) < 1e-7) {
            return false;
        }

        return $lat >= -90.0 && $lat <= 90.0 && $lng >= -180.0 && $lng <= 180.0;
    }

    /**
     * Google Nearby search origin: prefer Area Wise sub-area/area center when set,
     * otherwise fall back to the property map pin (latitude/longitude on propertys).
     *
     * @return array{lat: float, lng: float, origin: string}
     */
    protected function resolveSearchCoordinates(Property $property): array
    {
        $propertyLat = (float) $property->latitude;
        $propertyLng = (float) $property->longitude;

        if (! Schema::hasTable('area_listing_property_locations')) {
            return ['lat' => $propertyLat, 'lng' => $propertyLng, 'origin' => 'property_pin'];
        }

        $location = DB::table('area_listing_property_locations')->where('property_id', $property->id)->first();
        if (! $location || empty($location->area_id)) {
            return ['lat' => $propertyLat, 'lng' => $propertyLng, 'origin' => 'property_pin'];
        }

        if (! empty($location->sub_area_id) && class_exists(\App\Plugins\AreaListing\Models\SubArea::class)) {
            $subArea = \App\Plugins\AreaListing\Models\SubArea::find($location->sub_area_id);
            $subMatchesArea = $subArea
                && (int) $subArea->area_id === (int) $location->area_id;
            if ($subMatchesArea && $subArea->center_lat !== null && $subArea->center_lng !== null
                && $subArea->center_lat !== '' && $subArea->center_lng !== '') {
                return [
                    'lat' => (float) $subArea->center_lat,
                    'lng' => (float) $subArea->center_lng,
                    'origin' => 'sub_area_center',
                ];
            }
        }

        if (class_exists(\App\Plugins\AreaListing\Models\Area::class)) {
            $area = \App\Plugins\AreaListing\Models\Area::find($location->area_id);
            if ($area && $area->center_lat !== null && $area->center_lng !== null
                && $area->center_lat !== '' && $area->center_lng !== '') {
                return [
                    'lat' => (float) $area->center_lat,
                    'lng' => (float) $area->center_lng,
                    'origin' => 'area_center',
                ];
            }
        }

        return ['lat' => $propertyLat, 'lng' => $propertyLng, 'origin' => 'property_pin'];
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
            if ($subAreaModel && (int) $subAreaModel->area_id !== (int) ($location->area_id ?? 0)) {
                $subAreaModel = null;
            }
        }

        $areaName = $this->cleanLocationPart(
            ($areaModel?->name ?? null)
                ?? ($location->area_name ?? null)
                ?? ($location->detected_area_name ?? null)
                ?? ''
        );

        $subAreaName = $subAreaModel
            ? $this->cleanLocationPart(
                ($subAreaModel->name ?? null)
                    ?? ($location->sub_area_name ?? null)
                    ?? ($location->detected_sub_area_name ?? null)
                    ?? ''
            )
            : '';

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

    /**
     * Human-readable distance for API/UI (not raw cache math).
     */
    public function formatDistanceDisplayLabel(?int $meters): string
    {
        if ($meters === null || $meters < 0) {
            return 'Distance unavailable';
        }

        if ($meters === 0) {
            return 'Distance unavailable';
        }

        if ($meters < 50) {
            return '<50 m away';
        }

        if ($meters < 1000) {
            return $meters . ' m away';
        }

        $km = $meters / 1000;
        $formatted = number_format($km, 1, '.', '');

        if ((float) $formatted <= 0) {
            return 'Distance unavailable';
        }

        return $formatted . ' km away';
    }

    /** @deprecated Use formatDistanceDisplayLabel */
    public function formatDistanceText(int $meters): string
    {
        return $this->formatDistanceDisplayLabel($meters);
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

    /**
     * @param  \Illuminate\Support\Collection<int, NearbyPlaceOverride>  $matching
     */
    protected function pickPrimaryOverride($matching, ?NearbyPlaceOverride $resolved): ?NearbyPlaceOverride
    {
        if ($resolved) {
            return $resolved;
        }

        return $matching
            ->sortBy(fn (NearbyPlaceOverride $o) => $o->display_order ?? PHP_INT_MAX)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildAdminReview(int $propertyId, ?int $categoryId = null, array $filters = []): array
    {
        $property = Property::query()->find($propertyId);
        if (!$property) {
            return ['error' => 'Property not found', 'items' => []];
        }

        $latitude = (float) $property->latitude;
        $longitude = (float) $property->longitude;
        if (!$latitude || !$longitude) {
            return ['error' => 'Property coordinates unavailable', 'items' => []];
        }

        $propertyTitle = $property->title ?? ('Property #' . $propertyId);

        $categories = $categoryId
            ? NearbyCategory::query()->where('id', $categoryId)->get()
            : NearbyCategory::query()->active()->orderBy('sort_order')->get();

        $allOverrides = $this->overrideService->loadForProperty($propertyId);
        $items = [];

        foreach ($categories as $category) {
            $cached = $this->getCachedPlaces($propertyId, $category->id);
            $needsFetch = $cached->isEmpty() || $cached->contains(fn ($row) => !$row->expires_at || $row->expires_at->isPast());
            if ($needsFetch && $category->google_place_type) {
                $this->fetchAndStore($property, $category, $latitude, $longitude);
                $cached = $this->getCachedPlaces($propertyId, $category->id);
            }

            foreach ($cached as $row) {
                $evaluation = NearbyPlacesSettings::qualityFilterEnabled()
                    ? $this->qualityFilter->evaluateRow($row, $category, $latitude, $longitude)
                    : ['pass' => true, 'hide_reason' => null, 'quality_score' => 0, 'quality_flags' => []];

                $normalizedName = $this->overrideService->normalizeName((string) $row->name);
                $matching = $this->overrideService->matchingOverrides(
                    $allOverrides,
                    (string) $row->google_place_id,
                    $normalizedName,
                    $propertyId,
                    (int) $category->id
                );
                $resolved = $this->overrideService->resolveVisibility($evaluation, $matching);

                $placeTypes = $this->decodePlaceTypes($row->place_types);
                $item = [
                    'property_id' => $propertyId,
                    'property_title' => $propertyTitle,
                    'google_place_id' => $row->google_place_id,
                    'name' => $row->name,
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'category_slug' => $category->slug,
                    'distance_m' => $row->distance_m,
                    'distance_text' => $row->distance_text ?: $this->formatDistanceDisplayLabel($row->distance_m),
                    'rating' => $row->rating,
                    'review_count' => $row->user_ratings_total,
                    'quality_score' => $evaluation['quality_score'] ?? 0,
                    'quality_flags' => $evaluation['quality_flags'] ?? [],
                    'hidden_reason' => $resolved['hidden_reason'] ?? $evaluation['hide_reason'],
                    'filter_passed' => (bool) ($evaluation['pass'] ?? false),
                    'final_visible' => $resolved['visible'],
                    'visibility_status' => $resolved['status'],
                    'overrides' => $matching->map(fn (NearbyPlaceOverride $o) => [
                        'id' => $o->id,
                        'action' => $o->action,
                        'is_recommended' => $o->is_recommended,
                        'display_order' => $o->display_order,
                    ])->values()->all(),
                    'is_same_coordinates' => in_array('same_coordinates', $evaluation['quality_flags'] ?? [], true),
                    'is_unrated' => $row->rating === null || (float) $row->rating <= 0,
                    'override_ids' => $matching->pluck('id')->all(),
                ];

                if ($this->passesReviewFilters($item, $filters)) {
                    $items[] = $item;
                }
            }

            foreach ($this->overrideService->buildManualPlaceRows($allOverrides, $propertyId, (int) $category->id, $latitude, $longitude) as $manual) {
                $item = [
                    'property_id' => $propertyId,
                    'property_title' => $propertyTitle,
                    'google_place_id' => $manual['google_place_id'],
                    'name' => $manual['name'],
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'category_slug' => $category->slug,
                    'distance_m' => $manual['distance_m'],
                    'distance_text' => $manual['distance_text'],
                    'rating' => null,
                    'review_count' => null,
                    'quality_score' => 100,
                    'quality_flags' => ['manual'],
                    'hidden_reason' => null,
                    'filter_passed' => true,
                    'final_visible' => true,
                    'visibility_status' => 'manual',
                    'overrides' => isset($manual['_override']) ? [[
                        'id' => $manual['_override']->id,
                        'action' => 'manual',
                        'is_recommended' => $manual['_override']->is_recommended,
                        'display_order' => $manual['_override']->display_order,
                    ]] : [],
                    'is_same_coordinates' => false,
                    'is_unrated' => true,
                    'override_ids' => isset($manual['_override']) ? [$manual['_override']->id] : [],
                ];

                if ($this->passesReviewFilters($item, $filters)) {
                    $items[] = $item;
                }
            }
        }

        return [
            'scope' => 'single',
            'property_id' => $propertyId,
            'property_title' => $propertyTitle,
            'items' => $items,
            'totals' => [
                'all' => count($items),
                'visible' => count(array_filter($items, fn ($i) => $i['final_visible'])),
                'hidden' => count(array_filter($items, fn ($i) => !$i['final_visible'])),
            ],
        ];
    }

    /**
     * @param  array<int, int>  $propertyIds
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function buildAdminReviewAll(array $propertyIds, ?int $categoryId = null, array $filters = []): array
    {
        $items = [];
        $properties = [];

        foreach ($propertyIds as $propertyId) {
            $result = $this->buildAdminReview((int) $propertyId, $categoryId, $filters);
            if (isset($result['error'])) {
                continue;
            }

            foreach ($result['items'] as $item) {
                $items[] = $item;
            }

            $properties[] = [
                'id' => (int) $propertyId,
                'title' => $result['property_title'] ?? ('Property #' . $propertyId),
                'visible' => $result['totals']['visible'] ?? 0,
                'hidden' => $result['totals']['hidden'] ?? 0,
            ];
        }

        usort($items, function (array $a, array $b): int {
            $propertyCompare = ($a['property_id'] ?? 0) <=> ($b['property_id'] ?? 0);
            if ($propertyCompare !== 0) {
                return $propertyCompare;
            }

            return strcmp((string) ($a['category_name'] ?? ''), (string) ($b['category_name'] ?? ''));
        });

        return [
            'scope' => 'all',
            'property_count' => count($properties),
            'properties' => $properties,
            'items' => $items,
            'totals' => [
                'all' => count($items),
                'visible' => count(array_filter($items, fn ($i) => $i['final_visible'])),
                'hidden' => count(array_filter($items, fn ($i) => !$i['final_visible'])),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $filters
     */
    protected function passesReviewFilters(array $item, array $filters): bool
    {
        $status = (string) ($filters['status'] ?? 'all');
        if ($status === 'visible' && !$item['final_visible']) {
            return false;
        }
        if ($status === 'hidden' && $item['final_visible']) {
            return false;
        }
        if ($status === 'force_shown' && !in_array($item['visibility_status'], ['force_show', 'whitelist', 'trust', 'manual'], true)) {
            return false;
        }
        if ($status === 'force_hidden' && !in_array($item['visibility_status'], ['force_hidden', 'blacklisted'], true)) {
            return false;
        }
        if ($status === 'low_quality' && (float) ($item['quality_score'] ?? 0) >= 120) {
            return false;
        }
        if ($status === 'same_coordinate' && empty($item['is_same_coordinates'])) {
            return false;
        }
        if ($status === 'unrated' && empty($item['is_unrated'])) {
            return false;
        }

        $query = trim((string) ($filters['q'] ?? ''));
        if ($query !== '' && stripos((string) $item['name'], $query) === false && stripos((string) $item['google_place_id'], $query) === false) {
            return false;
        }

        return true;
    }

    /**
     * @return array{nearby:int, travel:int}
     */
    public function clearCacheForProperty(int $propertyId, ?int $categoryId = null): array
    {
        if ($categoryId !== null) {
            return [
                'nearby' => $this->clearCacheForPropertyCategory($propertyId, $categoryId),
                'travel' => 0,
            ];
        }

        $nearbyDeleted = (int) NearbyPlaceCache::query()->where('property_id', $propertyId)->delete();

        $travelDeleted = 0;
        if (Schema::hasTable('travel_time_cache')) {
            $travelDeleted = (int) DB::table('travel_time_cache')->where('property_id', $propertyId)->delete();
        }

        return ['nearby' => $nearbyDeleted, 'travel' => $travelDeleted];
    }

    public function clearCacheForPropertyCategory(int $propertyId, int $categoryId): int
    {
        return (int) NearbyPlaceCache::query()
            ->where('property_id', $propertyId)
            ->where('nearby_category_id', $categoryId)
            ->delete();
    }

    /**
     * @return array{nearby:int, travel:int}
     */
    public function clearAllCache(): array
    {
        $nearbyDeleted = (int) NearbyPlaceCache::query()->delete();

        $travelDeleted = 0;
        if (Schema::hasTable('travel_time_cache')) {
            $travelDeleted = (int) DB::table('travel_time_cache')->delete();
        }

        return ['nearby' => $nearbyDeleted, 'travel' => $travelDeleted];
    }
}
