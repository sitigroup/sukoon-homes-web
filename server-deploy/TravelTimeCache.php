<?php

namespace App\Plugins\NearbyPlaces\Models;

use Illuminate\Database\Eloquent\Model;

class TravelTimeCache extends Model
{
    protected $table = 'travel_time_cache';

    protected $fillable = [
        'property_id',
        'google_place_id',
        'duration_seconds',
        'duration_text',
        'fetched_at',
        'expires_at',
    ];

    protected $casts = [
        'fetched_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
