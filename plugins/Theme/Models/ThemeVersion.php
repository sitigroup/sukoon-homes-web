<?php

namespace App\Plugins\Theme\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeVersion extends Model
{
    protected $table = 'theme_versions';

    protected $fillable = [
        'version_number',
        'payload',
        'preset_slug',
        'action',
        'note',
        'created_by',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
