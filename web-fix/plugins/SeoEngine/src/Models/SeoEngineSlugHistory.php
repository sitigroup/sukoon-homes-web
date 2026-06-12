<?php

namespace App\Plugins\SeoEngine\Models;

use Illuminate\Database\Eloquent\Model;

class SeoEngineSlugHistory extends Model
{
    protected $table = 'seo_engine_slug_history';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'old_slug',
        'new_slug',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];
}
