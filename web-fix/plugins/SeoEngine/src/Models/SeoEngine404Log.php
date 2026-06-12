<?php

namespace App\Plugins\SeoEngine\Models;

use Illuminate\Database\Eloquent\Model;

class SeoEngine404Log extends Model
{
    protected $table = 'seo_engine_404_log';

    protected $fillable = [
        'path',
        'referrer',
        'user_agent',
        'ip',
        'hit_count',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];
}
