<?php

namespace App\Plugins\OwnerDashboard\Models;

use App\Models\Customer;
use App\Models\Property;
use App\Plugins\RentalAgreement\Models\RentalAgreement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OwnerTenancy extends Model
{
    protected $table = 'owner_tenancies';

    const STATUS_PENDING   = 'pending';
    const STATUS_ACTIVE    = 'active';
    const STATUS_NOTICE    = 'notice';
    const STATUS_MOVE_OUT  = 'move_out';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const FLOW_KYC_ONLY      = 'kyc_only';
    const FLOW_KYC_AGREEMENT = 'kyc_agreement';

    /** Statuses for which the owner dashboard is considered live/visible. */
    const VISIBLE_STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_NOTICE,
        self::STATUS_MOVE_OUT,
    ];

    protected $fillable = [
        'owner_customer_id',
        'owner_name',
        'owner_phone',
        'owner_email',
        'agent_customer_id',
        'tenant_customer_id',
        'property_id',
        'rental_agreement_id',
        'move_in_checklist_id',
        'status',
        'flow_type',
        'agreement_request_id',
        'monthly_rent',
        'security_deposit',
        'token_amount',
        'tenant_display_name',
        'owner_maintenance_mode',
        'maintenance_responsibility_override',
        'dashboard_enabled',
        'activated_at',
        'ended_at',
        'created_by_admin_id',
    ];

    protected $casts = [
        'owner_maintenance_mode' => 'string',
        'maintenance_responsibility_override' => 'array',
        'dashboard_enabled' => 'boolean',
        'activated_at'      => 'datetime',
        'ended_at'          => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'owner_customer_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'agent_customer_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'tenant_customer_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'rental_agreement_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(OwnerTenancyAuditLog::class, 'owner_tenancy_id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereIn('status', self::VISIBLE_STATUSES)
            ->where('dashboard_enabled', true);
    }

    public function isKycOnly(): bool
    {
        return ($this->flow_type ?? self::FLOW_KYC_AGREEMENT) === self::FLOW_KYC_ONLY;
    }

    public function flowTypeLabel(): string
    {
        return $this->isKycOnly() ? __('KYC only') : __('KYC + Agreement');
    }

    public function isVisible(): bool
    {
        return $this->dashboard_enabled
            && in_array($this->status, self::VISIBLE_STATUSES, true);
    }

    public function isPendingOwner(): bool
    {
        return $this->owner_customer_id === null;
    }

    public function pendingOwnerLabel(): string
    {
        if ($this->owner_name) {
            return (string) $this->owner_name;
        }

        return (string) ($this->owner_phone ?: __('Pending owner'));
    }
}
