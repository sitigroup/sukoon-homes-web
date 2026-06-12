<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvOrderDocument extends Model
{
    protected $table = 'tv_order_documents';

    protected $fillable = [
        'order_id',
        'doc_type',
        'file_path',
        'original_name',
        'mime_type',
        'size_bytes',
        'uploaded_by_customer_id',
        'deleted_at',
        'deleted_by_type',
        'deleted_by_id',
        'delete_reason',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'deleted_at' => 'datetime',
        'deleted_by_id' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(TvOrder::class, 'order_id');
    }
}
