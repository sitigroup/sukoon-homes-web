<?php

namespace App\Plugins\Theme\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeSetting extends Model
{
    protected $table = 'theme_settings';

    protected $fillable = [
        'draft_payload',
        'published_payload',
        'active_preset',
        'published_at',
        'published_by',
    ];

    protected $casts = [
        'draft_payload' => 'array',
        'published_payload' => 'array',
        'published_at' => 'datetime',
    ];
}
