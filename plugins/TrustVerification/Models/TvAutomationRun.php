<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvAutomationRun extends Model
{
    protected $table = 'tv_automation_runs';

    protected $fillable = [
        'order_id',
        'provider',
        'status',
        'trigger',
        'request_payload',
        'response_payload',
        'error_message',
        'checks_updated',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'checks_updated' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(TvOrder::class, 'order_id');
    }
}
