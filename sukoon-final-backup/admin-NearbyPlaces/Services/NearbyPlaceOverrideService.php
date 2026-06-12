<?php

namespace App\Plugins\NearbyPlaces\Services;

use App\Plugins\NearbyPlaces\Models\NearbyPlaceOverride;
use Illuminate\Support\Collection;

class NearbyPlaceOverrideService
{
    public function __construct(
        protected NearbyPlaceQualityFilter $qualityFilter,
    ) {
    }

    /**
     * @return Collection<int, NearbyPlaceOverride>
     */
    public function loadForProperty(int $propertyId): Collection
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('nearby_place_overrides')) {
            return collect();
        }

        return NearbyPlaceOverride::query()
            ->where(function ($query) use ($propertyId) {
                $query->whereNull('property_id')->orWhere('property_id', $propertyId);
            })
            ->orderByRaw('display_order IS NULL, display_order ASC')
            ->get();
    }

    /**
     * @param  Collection<int, NearbyPlaceOverride>  $allOverrides
     * @return Collection<int, NearbyPlaceOverride>
     */
    public function matchingOverrides(
        Collection $allOverrides,
        ?string $googlePlaceId,
        string $normalizedName,
        int $propertyId,
        int $categoryId,
    ): Collection {
        return $allOverrides->filter(function (NearbyPlaceOverride $override) use ($googlePlaceId, $normalizedName, $propertyId, $categoryId) {
            if ($override->property_id !== null && (int) $override->property_id !== $propertyId) {
                return false;
            }

            if ($override->category_id !== null && (int) $override->category_id !== $categoryId) {
                return false;
            }

            $overridePlaceId = trim((string) ($override->google_place_id ?? ''));
            if ($overridePlaceId !== '' && $googlePlaceId !== null && $overridePlaceId === $googlePlaceId) {
                return true;
            }

            $overrideName = trim((string) ($override->normalized_name ?? ''));
            if ($overrideName !== '' && $overrideName === $normalizedName) {
                return true;
            }

            return false;
        })->values();
    }

    /**
     * Priority: blacklist → force_hide → manual → whitelist/trust/force_show → quality filter
     *
     * @param  array{pass:bool,hide_reason:?string}  $evaluation
     * @param  Collection<int, NearbyPlaceOverride>  $matching
     * @return array{
     *   visible:bool,
     *   status:string,
     *   override:?NearbyPlaceOverride,
     *   hidden_reason:?string
     * }
     */
    public function resolveVisibility(array $evaluation, Collection $matching): array
    {
        $actions = $matching->pluck('action')->all();

        if (in_array(NearbyPlaceOverride::ACTION_BLACKLIST, $actions, true)) {
            return [
                'visible' => false,
                'status' => 'blacklisted',
                'override' => $matching->firstWhere('action', NearbyPlaceOverride::ACTION_BLACKLIST),
                'hidden_reason' => 'blacklisted',
            ];
        }

        if (in_array(NearbyPlaceOverride::ACTION_FORCE_HIDE, $actions, true)) {
            return [
                'visible' => false,
                'status' => 'force_hidden',
                'override' => $matching->firstWhere('action', NearbyPlaceOverride::ACTION_FORCE_HIDE),
                'hidden_reason' => 'force_hidden',
            ];
        }

        if (in_array(NearbyPlaceOverride::ACTION_MANUAL, $actions, true)) {
            return [
                'visible' => true,
                'status' => 'manual',
                'override' => $matching->firstWhere('action', NearbyPlaceOverride::ACTION_MANUAL),
                'hidden_reason' => null,
            ];
        }

        foreach ([
            NearbyPlaceOverride::ACTION_WHITELIST,
            NearbyPlaceOverride::ACTION_TRUST,
            NearbyPlaceOverride::ACTION_FORCE_SHOW,
        ] as $action) {
            if (in_array($action, $actions, true)) {
                return [
                    'visible' => true,
                    'status' => $action,
                    'override' => $matching->firstWhere('action', $action),
                    'hidden_reason' => null,
                ];
            }
        }

        $visible = (bool) ($evaluation['pass'] ?? true);

        return [
            'visible' => $visible,
            'status' => $visible ? 'visible' : 'filtered',
            'override' => null,
            'hidden_reason' => $visible ? null : ($evaluation['hide_reason'] ?? 'filtered'),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function applyOverrideToPublicRow(array $row, ?NearbyPlaceOverride $override): array
    {
        if (!$override) {
            return $row;
        }

        if ($override->display_name) {
            $row['name'] = $override->display_name;
        }

        if ($override->display_distance_text) {
            $row['distance_text'] = $override->display_distance_text;
        }

        if ($override->manual_lat !== null && $override->manual_lng !== null) {
            $row['latitude'] = (float) $override->manual_lat;
            $row['longitude'] = (float) $override->manual_lng;
        }

        if ($override->manual_direction_url) {
            $row['directions_url'] = $override->manual_direction_url;
            $row['show_directions'] = !$override->disable_directions;
        } elseif ($override->disable_directions) {
            $row['directions_url'] = '';
            $row['show_directions'] = false;
        }

        $row['is_recommended'] = (bool) $override->is_recommended;
        $row['display_order'] = $override->display_order;

        return $row;
    }

    /**
     * @param  Collection<int, NearbyPlaceOverride>  $allOverrides
     * @return array<int, array<string, mixed>>
     */
    public function buildManualPlaceRows(
        Collection $allOverrides,
        int $propertyId,
        int $categoryId,
        float $originLat,
        float $originLng,
    ): array {
        $manual = $allOverrides->filter(function (NearbyPlaceOverride $override) use ($propertyId, $categoryId) {
            if ($override->action !== NearbyPlaceOverride::ACTION_MANUAL) {
                return false;
            }

            if ((int) ($override->property_id ?? 0) !== $propertyId) {
                return false;
            }

            if ($override->category_id !== null && (int) $override->category_id !== $categoryId) {
                return false;
            }

            return true;
        });

        $rows = [];
        foreach ($manual as $override) {
            $lat = (float) ($override->manual_lat ?? $originLat);
            $lng = (float) ($override->manual_lng ?? $originLng);
            $distanceM = $this->qualityFilter->distanceMeters($originLat, $originLng, $lat, $lng);

            $rows[] = [
                'category_id' => $categoryId,
                'name' => $override->display_name ?: ($override->normalized_name ?: 'Manual place'),
                'distance_m' => $distanceM,
                'distance_text' => $override->display_distance_text ?: ($distanceM . ' m away'),
                'rating' => null,
                'review_count' => null,
                'is_open' => null,
                'google_place_id' => $override->google_place_id ?: ('manual-' . $override->id),
                'is_same_location' => false,
                'directions_url' => $override->disable_directions ? '' : (string) ($override->manual_direction_url ?? ''),
                'show_directions' => !$override->disable_directions && (string) ($override->manual_direction_url ?? '') !== '',
                'navigation_quality' => 100,
                'invalid_navigation' => false,
                'navigation_flags' => [],
                'vicinity' => null,
                'place_types' => ['establishment'],
                'quality_score' => 100,
                'quality_flags' => ['manual'],
                'is_recommended' => (bool) $override->is_recommended,
                'display_order' => $override->display_order,
                '_override' => $override,
            ];
        }

        return $rows;
    }

    public function normalizeName(string $name): string
    {
        return $this->qualityFilter->normalizeName($name);
    }

    /**
     * @param  array<int, array<string, mixed>>  $places
     * @return array<int, array<string, mixed>>
     */
    public function sortPlaces(array $places): array
    {
        usort($places, function (array $a, array $b): int {
            $rec = ((int) ($b['is_recommended'] ?? 0)) <=> ((int) ($a['is_recommended'] ?? 0));
            if ($rec !== 0) {
                return $rec;
            }

            $orderA = $a['display_order'] ?? PHP_INT_MAX;
            $orderB = $b['display_order'] ?? PHP_INT_MAX;
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            return ($a['distance_m'] ?? PHP_INT_MAX) <=> ($b['distance_m'] ?? PHP_INT_MAX);
        });

        return $places;
    }

    public function stripAdminFields(array $row): array
    {
        unset($row['_override'], $row['display_order']);

        return $row;
    }
}
