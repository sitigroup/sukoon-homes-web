<?php

namespace App\Plugins\NearbyPlaces\Models;

use Illuminate\Database\Eloquent\Model;

class NearbyPlaceOverride extends Model
{
    protected $table = 'nearby_place_overrides';

    public const ACTION_FORCE_SHOW = 'force_show';
    public const ACTION_FORCE_HIDE = 'force_hide';
    public const ACTION_TRUST = 'trust';
    public const ACTION_BLACKLIST = 'blacklist';
    public const ACTION_WHITELIST = 'whitelist';
    public const ACTION_MANUAL = 'manual';

    /** @var array<int, string> */
    public const ACTIONS = [
        self::ACTION_FORCE_SHOW,
        self::ACTION_FORCE_HIDE,
        self::ACTION_TRUST,
        self::ACTION_BLACKLIST,
        self::ACTION_WHITELIST,
        self::ACTION_MANUAL,
    ];

    protected $fillable = [
        'property_id',
        'category_id',
        'google_place_id',
        'normalized_name',
        'action',
        'display_name',
        'display_distance_text',
        'display_order',
        'is_recommended',
        'manual_lat',
        'manual_lng',
        'manual_direction_url',
        'disable_directions',
        'admin_note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'property_id' => 'integer',
        'category_id' => 'integer',
        'display_order' => 'integer',
        'is_recommended' => 'boolean',
        'manual_lat' => 'float',
        'manual_lng' => 'float',
        'disable_directions' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(NearbyCategory::class, 'category_id');
    }
}
