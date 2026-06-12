<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvPaymentWebhookEvent extends Model
{
    protected $table = 'tv_payment_webhook_events';

    protected $fillable = [
        'provider',
        'event_id',
        'order_id',
        'payment_transaction_id',
        'signature_hash',
        'payload_hash',
        'payment_status',
        'received_at',
        'processed_at',
        'rejected_reason',
        'raw_payload',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'raw_payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(TvOrder::class, 'order_id');
    }
}
