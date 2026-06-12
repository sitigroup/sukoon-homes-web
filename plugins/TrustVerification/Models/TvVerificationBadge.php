<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvVerificationBadge extends Model
{
    public const TYPE_OWNER = 'owner';

    public const TYPE_TENANT = 'tenant';

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REVOKED = 'revoked';

    protected $table = 'tv_verification_badges';

    protected $fillable = [
        'customer_id',
        'order_id',
        'badge_type',
        'badge_number',
        'status',
        'issued_at',
        'expires_at',
        'revoked_at',
        'revoke_reason',
        'metadata',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(TvOrder::class, 'order_id');
    }

    public function isPublicDisplayVerified(): bool
    {
        return $this->badge_type === self::TYPE_OWNER
            && $this->isCurrentlyVerified();
    }

    public function isCurrentlyVerified(): bool
    {
        if ($this->status !== self::STATUS_VERIFIED) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return ! $this->revoked_at;
    }

    public function displayTitle(): string
    {
        return $this->badge_type === self::TYPE_OWNER
            ? 'Sukoon Verified Owner'
            : 'Sukoon Verified Tenant';
    }
}
