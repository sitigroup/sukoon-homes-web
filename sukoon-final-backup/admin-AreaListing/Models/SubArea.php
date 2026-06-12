<?php

namespace App\Plugins\AreaListing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SubArea extends Model
{
    protected $table = 'area_listing_sub_areas';

    protected $fillable = [
        'area_id', 'name', 'normalized_name', 'slug', 'center_lat', 'center_lng',
        'radius_meters', 'polygon_json', 'sort_order', 'status', 'workflow_status', 'archived_at', 'seo_title', 'seo_description',
    ];

    protected $casts = [
        'status' => 'boolean',
        'polygon_json' => 'array',
        'center_lat' => 'decimal:7',
        'center_lng' => 'decimal:7',
        'archived_at' => 'datetime',
    ];

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    protected static function booted(): void
    {
        static::saving(function (SubArea $subArea) {
            $subArea->name = self::clean($subArea->name);
            $subArea->normalized_name = self::normalized($subArea->name);
            $subArea->slug = Str::slug($subArea->name);
            $subArea->workflow_status = $subArea->workflow_status ?: 'active';
            $subArea->status = $subArea->workflow_status === 'active';
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
