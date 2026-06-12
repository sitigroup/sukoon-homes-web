<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TvOrder extends Model
{
    protected $table = 'tv_orders';

    protected $fillable = [
        'order_number',
        'customer_id',
        'package_id',
        'order_type',
        'city_slug',
        'requester_name',
        'requester_email',
        'requester_phone',
        'status',
        'payment_status',
        'payment_transaction_id',
        'automation_status',
        'amount',
        'requester_notes',
        'admin_notes',
        'consent_given',
        'consent_text',
        'consent_ip',
        'consent_user_agent',
        'consent_given_at',
        'legal_version',
        'completed_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'consent_given' => 'boolean',
        'consent_given_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(TvPackage::class, 'package_id');
    }

    public function subject(): HasOne
    {
        return $this->hasOne(TvSubject::class, 'order_id');
    }

    public function checkItems(): HasMany
    {
        return $this->hasMany(TvCheckItem::class, 'order_id')->orderBy('id');
    }

    public function report(): HasOne
    {
        return $this->hasOne(TvReport::class, 'order_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TvOrderDocument::class, 'order_id')->orderBy('id');
    }

    public function automationRuns(): HasMany
    {
        return $this->hasMany(TvAutomationRun::class, 'order_id')->latest('id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(TvAuditLog::class, 'order_id')->latest('id');
    }

    public function referenceContacts(): HasMany
    {
        return $this->hasMany(TvReferenceContact::class, 'order_id')->orderBy('sort_order')->orderBy('id');
    }

    public function policeVerification(): HasOne
    {
        return $this->hasOne(TvPoliceVerification::class, 'order_id');
    }

    public function verificationBadge(): HasOne
    {
        return $this->hasOne(TvVerificationBadge::class, 'order_id');
    }
}
