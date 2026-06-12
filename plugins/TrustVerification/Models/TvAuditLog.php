<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvAuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'tv_audit_logs';

    protected $fillable = [
        'order_id',
        'admin_id',
        'customer_id',
        'action',
        'description',
        'ip',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(TvOrder::class, 'order_id');
    }

    public function actorLabel(): string
    {
        if ($this->admin_id) {
            return 'Admin #'.$this->admin_id;
        }

        if ($this->customer_id) {
            return 'Customer #'.$this->customer_id;
        }

        return 'System';
    }

    public function actionLabel(): string
    {
        return str_replace('_', ' ', $this->action);
    }
}
