<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvCheckItem extends Model
{
    protected $table = 'tv_check_items';

    protected $fillable = [
        'order_id',
        'check_key',
        'label',
        'status',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(TvOrder::class, 'order_id');
    }
}
