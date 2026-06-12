<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvReport extends Model
{
    protected $table = 'tv_reports';

    protected $fillable = [
        'order_id',
        'file_path',
        'risk_level',
        'summary',
        'uploaded_by',
        'deleted_at',
        'deleted_by_type',
        'deleted_by_id',
        'delete_reason',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
        'deleted_by_id' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(TvOrder::class, 'order_id');
    }
}
