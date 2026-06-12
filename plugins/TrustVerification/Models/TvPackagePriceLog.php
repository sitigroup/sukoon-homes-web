<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvPackagePriceLog extends Model
{
    protected $table = 'tv_package_price_logs';

    protected $fillable = [
        'package_id',
        'old_price',
        'new_price',
        'changed_by',
    ];

    protected $casts = [
        'old_price' => 'integer',
        'new_price' => 'integer',
        'changed_by' => 'integer',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(TvPackage::class, 'package_id');
    }
}
