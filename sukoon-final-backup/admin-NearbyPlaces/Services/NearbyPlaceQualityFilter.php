<?php

namespace App\Plugins\NearbyPlaces\Services;

use App\Plugins\NearbyPlaces\Models\NearbyCategory;
use App\Plugins\NearbyPlaces\Models\NearbyPlaceCache;

class NearbyPlaceQualityFilter
{
    /** @var array<int, string> */
    public const GENERIC_TYPES = [
        'locality',
        'political',
        'route',
        'geocode',
        'plus_code',
        'neighborhood',
    ];

    /** @var array<int, string> */
    public const BUSINESS_TYPES = [
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

    /**
     * @param  array<string, mixed>  $place
     * @return array{
     *   pass:bool,
     *   hide_reason:?string,
     *   quality_score:float,
     *   quality_flags:array<int,string>
     * }
     */
    public function evaluateCandidate(array $place, NearbyCategory $category, float $originLat, float $originLng): array
    {
        $placeTypes = $this->decodePlaceTypes($place['place_types'] ?? []);

        return $this->evaluate(
            [
                'google_place_id' => (string) ($place['google_place_id'] ?? ''),
                'name' => (string) ($place['name'] ?? ''),
                'rating' => isset($place['rating']) ? (float) $place['rating'] : null,
                'user_ratings_total' => isset($place['user_ratings_total']) ? (int) $place['user_ratings_total'] : null,
                'distance_m' => isset($place['distance_m']) ? (int) $place['distance_m'] : null,
                'latitude' => (float) ($place['latitude'] ?? 0),
                'longitude' => (float) ($place['longitude'] ?? 0),
                'is_open' => $place['is_open'] ?? null,
                'business_status' => (string) ($place['business_status'] ?? ''),
                'place_types' => $placeTypes,
            ],
            $category,
            $originLat,
            $originLng,
            $placeTypes
        );
    }

    /**
     * @return array{
     *   pass:bool,
     *   hide_reason:?string,
     *   quality_score:float,
     *   quality_flags:array<int,string>
     * }
     */
    public function evaluateRow(NearbyPlaceCache $row, NearbyCategory $category, float $originLat, float $originLng): array
    {
        $placeTypes = $this->decodePlaceTypes($row->place_types);

        return $this->evaluate(
            [
                'google_place_id' => (string) ($row->google_place_id ?? ''),
                'name' => (string) ($row->name ?? ''),
                'rating' => $row->rating !== null ? (float) $row->rating : null,
                'user_ratings_total' => $row->user_ratings_total !== null ? (int) $row->user_ratings_total : null,
                'distance_m' => $row->distance_m !== null ? (int) $row->distance_m : null,
                'latitude' => (float) $row->latitude,
                'longitude' => (float) $row->longitude,
                'is_open' => $row->is_open,
                'business_status' => (string) ($row->business_status ?? ''),
                'place_types' => $placeTypes,
            ],
            $category,
            $originLat,
            $originLng,
            $placeTypes
        );
    }

    /**
     * @param  array<string, mixed>  $place
     * @param  array<int, string>  $placeTypes
     * @return array{
     *   pass:bool,
     *   hide_reason:?string,
     *   quality_score:float,
     *   quality_flags:array<int,string>
     * }
     */
    protected function evaluate(
        array $place,
        NearbyCategory $category,
        float $originLat,
        float $originLng,
        array $placeTypes,
    ): array {
        $flags = [];
        $hideReason = null;

        $businessStatus = strtoupper(trim((string) ($place['business_status'] ?? '')));
        if ($businessStatus === 'CLOSED_PERMANENTLY') {
            return $this->result(false, 'permanently_closed', 0, ['closed_permanently']);
        }

        if ($businessStatus === 'CLOSED_TEMPORARILY') {
            $flags[] = 'closed_temporarily';
        }

        if ($this->categoryHidesGeneric($category) && $this->hasGenericType($placeTypes)) {
            return $this->result(false, 'generic_type', 0, ['generic_type']);
        }

        $distanceM = $place['distance_m'];
        if ($distanceM === null && $this->hasUsableCoordinates($originLat, $originLng, (float) $place['latitude'], (float) $place['longitude'])) {
            $distanceM = $this->distanceMeters($originLat, $originLng, (float) $place['latitude'], (float) $place['longitude']);
        }

        $maxDistance = (int) ($category->max_distance_m ?: $category->default_radius_m ?: 0);
        if ($maxDistance > 0 && $distanceM !== null && $distanceM > $maxDistance) {
            return $this->result(false, 'beyond_max_distance', 0, ['beyond_max_distance']);
        }

        $coordsIdentical = $this->coordinatesAreIdentical(
            $originLat,
            $originLng,
            (float) $place['latitude'],
            (float) $place['longitude']
        );

        $isRealBusiness = $this->isRealBusiness($placeTypes);

        if ($coordsIdentical) {
            $flags[] = 'same_coordinates';

            if ($category->hide_suspicious_same_location && $isRealBusiness) {
                if (!$this->passesSameLocationBusinessCheck($place, $category)) {
                    return $this->result(false, 'suspicious_same_location', 0, array_values(array_unique([...$flags, 'suspicious_same_location'])));
                }
            } elseif ($coordsIdentical && !$isRealBusiness && $this->categoryHidesGeneric($category)) {
                return $this->result(false, 'generic_same_location', 0, array_values(array_unique([...$flags, 'generic_type'])));
            }
        }

        $rating = $place['rating'];
        $reviews = max(0, (int) ($place['user_ratings_total'] ?? 0));
        $minRating = (float) ($category->min_rating ?? 3.5);
        $minReviews = max(0, (int) ($category->min_reviews ?? 3));
        $allowUnrated = (bool) ($category->allow_unrated ?? false);

        $hasRating = $rating !== null && $rating > 0;

        if (!$hasRating) {
            $flags[] = 'unrated';
            if (!$allowUnrated) {
                return $this->result(false, 'unrated', 0, $flags);
            }
        } elseif ($rating < $minRating) {
            return $this->result(false, 'below_min_rating', 0, array_values(array_unique([...$flags, 'low_rating'])));
        }

        if ($reviews < $minReviews) {
            $flags[] = 'low_reviews';
            if ($hasRating || !$allowUnrated) {
                return $this->result(false, 'below_min_reviews', 0, $flags);
            }
        }

        $score = $this->computeQualityScore(
            $place,
            $category,
            $placeTypes,
            $distanceM,
            $flags,
            $coordsIdentical
        );

        return $this->result(true, null, $score, array_values(array_unique($flags)));
    }

    /**
     * @param  array<int, array<string, mixed>>  $places
     * @return array<int, array<string, mixed>>
     */
    public function deduplicate(array $places): array
    {
        $kept = [];
        $seenIds = [];
        $seenNames = [];

        foreach ($places as $place) {
            $placeId = trim((string) ($place['google_place_id'] ?? ''));
            if ($placeId !== '' && isset($seenIds[$placeId])) {
                continue;
            }

            $normalized = $this->normalizeName((string) ($place['name'] ?? ''));
            $lat = (float) ($place['latitude'] ?? 0);
            $lng = (float) ($place['longitude'] ?? 0);
            $duplicate = false;

            foreach ($seenNames as $seen) {
                if ($seen['normalized'] !== $normalized) {
                    continue;
                }

                if ($this->distanceMeters($lat, $lng, $seen['lat'], $seen['lng']) <= 100) {
                    $duplicate = true;
                    break;
                }
            }

            if ($duplicate) {
                continue;
            }

            if ($placeId !== '') {
                $seenIds[$placeId] = true;
            }

            $seenNames[] = [
                'normalized' => $normalized,
                'lat' => $lat,
                'lng' => $lng,
            ];

            $kept[] = $place;
        }

        return $kept;
    }

    /**
     * @param  array<int, array<string, mixed>>  $places
     * @return array<int, array<string, mixed>>
     */
    public function sortByQualityScore(array $places): array
    {
        usort($places, function (array $a, array $b): int {
            $scoreCmp = ($b['_quality_score'] ?? 0) <=> ($a['_quality_score'] ?? 0);
            if ($scoreCmp !== 0) {
                return $scoreCmp;
            }

            return ($a['distance_m'] ?? PHP_INT_MAX) <=> ($b['distance_m'] ?? PHP_INT_MAX);
        });

        return $places;
    }

    protected function passesSameLocationBusinessCheck(array $place, NearbyCategory $category): bool
    {
        $placeId = trim((string) ($place['google_place_id'] ?? ''));
        $name = trim((string) ($place['name'] ?? ''));

        if ($placeId === '' || mb_strlen($name) < 3 || strcasecmp($name, 'Unnamed place') === 0) {
            return false;
        }

        $rating = $place['rating'];
        $reviews = max(0, (int) ($place['user_ratings_total'] ?? 0));
        $minRating = (float) ($category->min_rating ?? 3.5);
        $minReviews = max(0, (int) ($category->min_reviews ?? 3));

        if ($rating !== null && $rating >= $minRating) {
            return true;
        }

        if ($reviews >= $minReviews) {
            return true;
        }

        return $rating !== null && $rating > 0 && $reviews >= 1;
    }

    /**
     * @param  array<string, mixed>  $place
     * @param  array<int, string>  $placeTypes
     * @param  array<int, string>  $flags
     */
    protected function computeQualityScore(
        array $place,
        NearbyCategory $category,
        array $placeTypes,
        ?int $distanceM,
        array $flags,
        bool $coordsIdentical,
    ): float {
        $score = 0.0;

        $rating = $place['rating'];
        if ($rating !== null && $rating > 0) {
            $score += $rating * 20;
        }

        $reviews = max(0, (int) ($place['user_ratings_total'] ?? 0));
        $score += min($reviews, 100) * 0.3;

        if ($distanceM !== null) {
            $score += max(0, 50 - ($distanceM / 100));
        }

        if ($place['is_open'] === true) {
            $score += 5;
        }

        if ($this->matchesCategoryType($placeTypes, (string) ($category->google_place_type ?? ''))) {
            $score += 10;
        }

        if ($coordsIdentical) {
            $score -= 25;
        }

        if (in_array('closed_temporarily', $flags, true)) {
            $score -= 15;
        }

        if (in_array('unrated', $flags, true)) {
            $score -= 10;
        }

        if (in_array('low_reviews', $flags, true)) {
            $score -= 10;
        }

        if (in_array('generic_type', $flags, true)) {
            $score -= 40;
        }

        return round(max(0, $score), 1);
    }

    /**
     * @param  array<int, string>  $flags
     * @return array{
     *   pass:bool,
     *   hide_reason:?string,
     *   quality_score:float,
     *   quality_flags:array<int,string>
     * }
     */
    protected function result(bool $pass, ?string $hideReason, float $score, array $flags): array
    {
        return [
            'pass' => $pass,
            'hide_reason' => $hideReason,
            'quality_score' => $score,
            'quality_flags' => array_values(array_unique($flags)),
        ];
    }

    protected function categoryHidesGeneric(NearbyCategory $category): bool
    {
        return (bool) ($category->hide_generic_places ?? true);
    }

    /**
     * @param  array<int, string>  $placeTypes
     */
    protected function hasGenericType(array $placeTypes): bool
    {
        foreach ($placeTypes as $type) {
            if (in_array($type, self::GENERIC_TYPES, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $placeTypes
     */
    protected function isRealBusiness(array $placeTypes): bool
    {
        foreach ($placeTypes as $type) {
            if (in_array($type, self::BUSINESS_TYPES, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, string>  $placeTypes
     */
    protected function matchesCategoryType(array $placeTypes, string $categoryType): bool
    {
        $categoryType = trim($categoryType);
        if ($categoryType === '') {
            return false;
        }

        return in_array($categoryType, $placeTypes, true);
    }

    /**
     * @return array<int, string>
     */
    public function decodePlaceTypes(mixed $raw): array
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

    public function normalizeName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $name) ?? $name;
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return trim($name);
    }

    protected function coordinatesAreIdentical(float $lat1, float $lng1, float $lat2, float $lng2): bool
    {
        return abs($lat1 - $lat2) < 0.00005 && abs($lng1 - $lng2) < 0.00005;
    }

    protected function hasUsableCoordinates(float $lat, float $lng, float $lat2, float $lng2): bool
    {
        return is_finite($lat) && is_finite($lng) && is_finite($lat2) && is_finite($lng2)
            && !(abs($lat) < 1e-7 && abs($lng) < 1e-7)
            && !(abs($lat2) < 1e-7 && abs($lng2) < 1e-7);
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
     * @param  array<int, string>  $placeTypes
     * @return array{
     *   navigation_quality:int,
     *   invalid_navigation:bool,
     *   show_directions:bool,
     *   navigation_flags:array<int,string>
     * }
     */
    public function evaluateNavigation(
        float $originLat,
        float $originLng,
        float $destLat,
        float $destLng,
        array $placeTypes,
        ?string $placeId,
        ?string $name,
        ?string $vicinity,
        string $distanceText,
        bool $coordsIdentical,
        bool $hasDirectionsUrl,
    ): array {
        $flags = [];
        $name = trim((string) $name);
        $vicinity = trim((string) $vicinity);
        $placeId = trim((string) ($placeId ?? ''));

        $plusCodeInText = $this->containsPlusCode($name) || $this->containsPlusCode($vicinity);
        $plusCodeOnly = $this->isPlusCodeOnly($placeTypes);
        $weakPoi = $this->isWeakNavigationPoi($placeTypes);

        if ($plusCodeInText) {
            $flags[] = 'plus_code_address';
        }

        if ($plusCodeOnly) {
            $flags[] = 'plus_code_only';
        }

        if ($weakPoi) {
            $flags[] = 'weak_poi_type';
        }

        if ($coordsIdentical) {
            $flags[] = 'same_coordinates';
        }

        $invalidNavigation = $plusCodeInText || $plusCodeOnly || $weakPoi;

        $hasOrigin = $this->hasUsablePoint($originLat, $originLng);
        $hasDest = $this->hasUsablePoint($destLat, $destLng);

        if (!$hasOrigin || !$hasDest || !$hasDirectionsUrl) {
            $invalidNavigation = true;
            $flags[] = 'invalid_routing';
        }

        if ($invalidNavigation) {
            $navigationQuality = 0;
        } elseif (!$coordsIdentical) {
            $navigationQuality = 100;
        } elseif ($placeId !== '' && $name !== '' && mb_strlen($name) >= 3 && !$plusCodeInText) {
            $navigationQuality = 70;
        } elseif ($coordsIdentical) {
            $navigationQuality = 40;
        } else {
            $navigationQuality = 0;
            $invalidNavigation = true;
            $flags[] = 'invalid_routing';
        }

        $distanceUnavailable = $distanceText === 'Distance unavailable';
        $hideByDistanceRule = $distanceUnavailable && (
            $coordsIdentical
            || $plusCodeOnly
            || $invalidNavigation
            || $navigationQuality === 0
        );

        $showDirections = $navigationQuality >= 60
            && $hasDirectionsUrl
            && !$hideByDistanceRule
            && !$invalidNavigation;

        if ($navigationQuality < 60) {
            $flags[] = 'navigation_blocked';
        }

        return [
            'navigation_quality' => $navigationQuality,
            'invalid_navigation' => $invalidNavigation || $navigationQuality < 60 || $hideByDistanceRule,
            'show_directions' => $showDirections,
            'navigation_flags' => array_values(array_unique($flags)),
        ];
    }

    public function containsPlusCode(string $text): bool
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }

        return (bool) preg_match('/\b[A-HJ-NP-Z0-9]{4,}\+[A-HJ-NP-Z0-9]{2,}\b/u', $text);
    }

    /**
     * @param  array<int, string>  $placeTypes
     */
    public function isPlusCodeOnly(array $placeTypes): bool
    {
        if (!in_array('plus_code', $placeTypes, true)) {
            return false;
        }

        return !$this->isRealBusiness($placeTypes);
    }

    /**
     * @param  array<int, string>  $placeTypes
     */
    public function isWeakNavigationPoi(array $placeTypes): bool
    {
        if ($this->isRealBusiness($placeTypes)) {
            return false;
        }

        return $this->hasGenericType($placeTypes);
    }

    protected function hasUsablePoint(float $lat, float $lng): bool
    {
        if (!is_finite($lat) || !is_finite($lng)) {
            return false;
        }

        if (abs($lat) < 1e-7 && abs($lng) < 1e-7) {
            return false;
        }

        return $lat >= -90.0 && $lat <= 90.0 && $lng >= -180.0 && $lng <= 180.0;
    }
}
