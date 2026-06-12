<?php

namespace App\Plugins\AreaListing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Area extends Model
{
    protected $table = 'area_listing_areas';

    protected $fillable = [
        'state_id', 'city_id', 'city_name', 'city', 'state', 'country', 'name', 'normalized_name', 'slug', 'center_lat', 'center_lng',
        'radius_meters', 'polygon_json', 'sort_order', 'status', 'workflow_status', 'archived_at', 'seo_title', 'seo_description',
    ];

    protected $casts = [
        'status' => 'boolean',
        'polygon_json' => 'array',
        'center_lat' => 'decimal:7',
        'center_lng' => 'decimal:7',
        'archived_at' => 'datetime',
    ];

    public function stateModel()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function cityModel()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    public function subAreas()
    {
        return $this->hasMany(SubArea::class, 'area_id');
    }

    protected static function booted(): void
    {
        static::saving(function (Area $area) {
            if ($area->city_id && ($area->city_name === '' || $area->city_name === null || $area->state === '' || $area->state === null || $area->country === '' || $area->country === null)) {
                $city = City::find($area->city_id);
                $area->city_name = $area->city_name ?: $city?->name;
                $area->state = $area->state ?: $city?->state;
                $area->country = $area->country ?: $city?->country;
            }

            $area->city_name = self::clean($area->city_name ?: $area->city);
            $area->name = self::clean($area->name);
            $area->city = self::clean($area->city ?: $area->city_name);
            $area->state = self::clean($area->state);
            $area->country = self::clean($area->country ?: 'India');
            $area->normalized_name = self::normalized($area->name);
            $area->slug = Str::slug($area->name);
            $area->workflow_status = $area->workflow_status ?: 'active';
            $area->status = $area->workflow_status === 'active';
        });
    }

    private static function clean($value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', (string) $value));
        return $value === '' ? '' : Str::title(Str::lower($value));
    }

    public function scopeActive($query)
    {
        return $query->where('status', true)->where('workflow_status', 'active');
    }

    private static function normalized($value): string
    {
        return Str::of((string) $value)->lower()->squish()->value();
    }
}
