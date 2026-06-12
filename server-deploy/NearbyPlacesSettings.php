<?php

namespace App\Plugins\NearbyPlaces\Support;

use App\Models\Setting;

class NearbyPlacesSettings
{
    public const ENABLED = 'nearby_places_enabled';
    public const DEFAULT_RADIUS_M = 'nearby_places_default_radius_m';
    public const DEFAULT_MAX_RESULTS = 'nearby_places_default_max_results';
    public const CACHE_TTL_HOURS = 'nearby_places_cache_ttl_hours';
    public const TRAVEL_TIME_ENABLED = 'nearby_places_travel_time_enabled';
    public const TRAVEL_CACHE_TTL_HOURS = 'nearby_places_travel_cache_ttl_hours';

    public static function allKeys(): array
    {
        return [
            self::ENABLED,
            self::DEFAULT_RADIUS_M,
            self::DEFAULT_MAX_RESULTS,
            self::CACHE_TTL_HOURS,
            self::TRAVEL_TIME_ENABLED,
            self::TRAVEL_CACHE_TTL_HOURS,
        ];
    }

    public static function get(string $key, $default = null)
    {
        $value = Setting::where('type', $key)->value('data');

        return $value === null ? $default : $value;
    }

    public static function isEnabled(): bool
    {
        return self::get(self::ENABLED, '1') === '1';
    }

    public static function defaultRadiusM(): int
    {
        return max(100, (int) self::get(self::DEFAULT_RADIUS_M, 2000));
    }

    public static function defaultMaxResults(): int
    {
        return max(1, min(20, (int) self::get(self::DEFAULT_MAX_RESULTS, 5)));
    }

    public static function cacheTtlHours(): int
    {
        return max(1, (int) self::get(self::CACHE_TTL_HOURS, 168));
    }

    public static function travelTimeEnabled(): bool
    {
        return self::get(self::TRAVEL_TIME_ENABLED, '0') === '1';
    }

    public static function travelCacheTtlHours(): int
    {
        return max(1, (int) self::get(self::TRAVEL_CACHE_TTL_HOURS, 168));
    }
}
