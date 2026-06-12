<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvReferenceContact extends Model
{
    protected $table = 'tv_reference_contacts';

    protected $fillable = [
        'order_id',
        'reference_type',
        'name',
        'relation',
        'mobile',
        'email',
        'notes',
        'status',
        'admin_notes',
        'call_outcome',
        'contacted_at',
        'verified_at',
        'last_status_at',
        'external_ref_id',
        'sort_order',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
        'verified_at' => 'datetime',
        'last_status_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(TvOrder::class, 'order_id');
    }
}
