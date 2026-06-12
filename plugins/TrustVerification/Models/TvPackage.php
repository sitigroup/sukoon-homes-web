<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TvPackage extends Model
{
    protected $table = 'tv_packages';

    protected $fillable = [
        'type',
        'city_slug',
        'name',
        'slug',
        'price',
        'currency',
        'delivery_hours',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'is_active' => 'boolean',
        'price' => 'integer',
        'delivery_hours' => 'integer',
        'sort_order' => 'integer',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(TvOrder::class, 'package_id');
    }

    public function priceLogs(): HasMany
    {
        return $this->hasMany(TvPackagePriceLog::class, 'package_id')->latest('id');
    }
}
