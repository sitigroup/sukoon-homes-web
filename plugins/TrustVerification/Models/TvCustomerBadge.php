<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvCustomerBadge extends Model
{
    protected $table = 'tv_customer_badges';

    protected $fillable = [
        'customer_id',
        'badge_id',
        'assigned_by',
        'auto_assigned',
        'notes',
    ];

    protected $casts = [
        'customer_id' => 'integer',
        'assigned_by' => 'integer',
        'auto_assigned' => 'boolean',
    ];

    public function badge(): BelongsTo
    {
        return $this->belongsTo(TvTrustBadge::class, 'badge_id');
    }
}
