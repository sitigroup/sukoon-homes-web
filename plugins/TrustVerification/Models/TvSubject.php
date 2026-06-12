<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvSubject extends Model
{
    protected $table = 'tv_subjects';

    protected $fillable = [
        'order_id',
        'subject_type',
        'full_name',
        'phone',
        'email',
        'current_address',
        'permanent_address',
        'property_address',
        'id_type',
        'id_number_hint',
        'employment_company',
        'employment_role',
        'consent_given',
        'consent_at',
    ];

    protected $casts = [
        'consent_given' => 'boolean',
        'consent_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(TvOrder::class, 'order_id');
    }
}
