<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvSampleReport extends Model
{
    public const TYPE_TENANT = 'tenant';

    public const TYPE_OWNER = 'owner';

    protected $table = 'tv_sample_reports';

    protected $fillable = [
        'report_type',
        'title',
        'description',
        'city_slug',
        'package_id',
        'file_path',
        'original_filename',
        'file_size_bytes',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'file_size_bytes' => 'integer',
        'sort_order' => 'integer',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(TvPackage::class, 'package_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('report_type', $type);
    }
}
