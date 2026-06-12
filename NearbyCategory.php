<?php

namespace App\Plugins\NearbyPlaces\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NearbyCategory extends Model
{
    use SoftDeletes;

    protected $table = 'nearby_categories';

    protected $fillable = [
        'name',
        'slug',
        'google_place_type',
        'icon',
        'default_radius_m',
        'max_results',
        'min_rating',
        'min_reviews',
        'allow_unrated',
        'hide_generic_places',
        'hide_suspicious_same_location',
        'max_distance_m',
        'max_results_after_filter',
        'cache_ttl_hours',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'default_radius_m' => 'integer',
        'max_results' => 'integer',
        'min_rating' => 'float',
        'min_reviews' => 'integer',
        'allow_unrated' => 'boolean',
        'hide_generic_places' => 'boolean',
        'hide_suspicious_same_location' => 'boolean',
        'max_distance_m' => 'integer',
        'max_results_after_filter' => 'integer',
        'cache_ttl_hours' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function effectiveCacheTtlHours(): int
    {
        if ($this->cache_ttl_hours !== null && (int) $this->cache_ttl_hours > 0) {
            return (int) $this->cache_ttl_hours;
        }

        return \App\Plugins\NearbyPlaces\Support\NearbyPlacesSettings::cacheTtlHours();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function formattedIconClass(): ?string
    {
        return self::normalizeIconClass($this->icon);
    }

    public static function normalizeIconClass(?string $icon): ?string
    {
        $icon = trim((string) $icon);
        if ($icon === '') {
            return null;
        }

        if (preg_match('/\b(fas|far|fab|fa-solid|fa-regular|fa-brands)\b/i', $icon)) {
            return $icon;
        }

        if (str_starts_with($icon, 'fa-')) {
            return 'fas ' . $icon;
        }

        return 'fas fa-' . ltrim($icon, '-');
    }
}
