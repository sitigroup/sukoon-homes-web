<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TvCity extends Model
{
    protected $table = 'tv_cities';

    protected $fillable = [
        'slug',
        'label',
        'is_enabled',
        'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function packages(): HasMany
    {
        return $this->hasMany(TvPackage::class, 'city_slug', 'slug');
    }
}
