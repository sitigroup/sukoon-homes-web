<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;

class TvTrustScore extends Model
{
    protected $table = 'tv_trust_scores';

    protected $fillable = [
        'customer_id',
        'trust_score',
        'manual_adjustment',
        'score_breakdown_json',
        'public_visible',
        'last_calculated_at',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'trust_score' => 'integer',
        'manual_adjustment' => 'integer',
        'score_breakdown_json' => 'array',
        'public_visible' => 'boolean',
        'last_calculated_at' => 'datetime',
    ];
}
