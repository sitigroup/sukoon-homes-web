<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TvRiskProfile extends Model
{
    protected $table = 'tv_risk_profiles';

    protected $fillable = [
        'customer_id',
        'risk_score',
        'risk_level',
        'manual_override',
        'notes',
        'last_calculated_at',
    ];

    protected $casts = [
        'risk_score' => 'integer',
        'manual_override' => 'integer',
        'last_calculated_at' => 'datetime',
    ];

    public function signals(): HasMany
    {
        return $this->hasMany(TvRiskSignal::class, 'customer_id', 'customer_id');
    }
}
