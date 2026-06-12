<?php

namespace App\Plugins\NearbyPlaces\Models;

use Illuminate\Database\Eloquent\Model;

class NearbyPlaceCache extends Model
{
    protected $table = 'nearby_place_cache';

    protected $fillable = [
        'property_id',
        'nearby_category_id',
        'google_place_id',
        'name',
        'rating',
        'user_ratings_total',
        'business_status',
        'distance_m',
        'distance_text',
        'is_open',
        'latitude',
        'longitude',
        'vicinity',
        'place_types',
        'quality_passed',
        'hidden_reason',
        'fetched_at',
        'expires_at',
    ];

    protected $casts = [
        'rating' => 'float',
        'user_ratings_total' => 'integer',
        'distance_m' => 'integer',
        'is_open' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'place_types' => 'array',
        'quality_passed' => 'boolean',
        'fetched_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(NearbyCategory::class, 'nearby_category_id');
    }
}
