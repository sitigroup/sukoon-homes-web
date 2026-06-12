<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvRiskSignal extends Model
{
    protected $table = 'tv_risk_signals';

    protected $fillable = [
        'order_id',
        'customer_id',
        'signal_type',
        'risk_points',
        'source',
        'notes',
        'metadata_json',
    ];

    protected $casts = [
        'risk_points' => 'integer',
        'metadata_json' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(TvOrder::class, 'order_id');
    }
}
