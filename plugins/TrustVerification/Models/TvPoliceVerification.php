<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvPoliceVerification extends Model
{
    protected $table = 'tv_police_verifications';

    protected $fillable = [
        'order_id',
        'provider',
        'verification_type',
        'status',
        'police_station_name',
        'city',
        'district',
        'state',
        'applicant_mobile',
        'reference_number',
        'acknowledgement_document_path',
        'acknowledgement_original_name',
        'acknowledgement_mime',
        'acknowledgement_size',
        'certificate_document_path',
        'certificate_original_name',
        'certificate_mime',
        'certificate_size',
        'status_check_url',
        'provider_reference_number',
        'provider_status',
        'provider_last_checked_at',
        'provider_raw_response',
        'admin_notes',
        'officer_notes',
        'rejection_reason',
        'customer_notes',
        'submitted_at',
        'reviewed_at',
        'completed_at',
        'rejected_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'provider_raw_response' => 'array',
        'provider_last_checked_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'acknowledgement_size' => 'integer',
        'certificate_size' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(TvOrder::class, 'order_id');
    }
}
