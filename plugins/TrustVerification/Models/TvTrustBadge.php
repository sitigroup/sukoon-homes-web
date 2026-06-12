<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TvTrustBadge extends Model
{
    protected $table = 'tv_trust_badges';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'priority',
        'active',
        'auto_assign',
        'color',
        'icon',
    ];

    protected $casts = [
        'priority' => 'integer',
        'active' => 'boolean',
        'auto_assign' => 'boolean',
    ];

    public function customerBadges(): HasMany
    {
        return $this->hasMany(TvCustomerBadge::class, 'badge_id');
    }
}
