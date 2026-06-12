<?php

namespace App\Plugins\SeoEngine\Models;

use Illuminate\Database\Eloquent\Model;

class SeoEngineLocalityStat extends Model
{
    protected $table = 'seo_engine_locality_stats';

    protected $fillable = [
        'area_id',
        'sub_area_id',
        'period',
        'avg_rent',
        'min_rent',
        'max_rent',
        'listing_count',
        'bhk_mix',
        'mom_change_pct',
    ];

    protected $casts = [
        'bhk_mix' => 'array',
        'mom_change_pct' => 'decimal:2',
    ];
}
