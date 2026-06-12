<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;

class TvTenantReliability extends Model
{
    protected $table = 'tv_tenant_reliability';

    protected $fillable = [
        'customer_id',
        'verification_completion',
        'reliability_level',
        'verification_status',
        'public_visible',
        'summary_json',
        'last_calculated_at',
    ];

    protected $casts = [
        'verification_completion' => 'integer',
        'public_visible' => 'boolean',
        'summary_json' => 'array',
        'last_calculated_at' => 'datetime',
    ];
}
