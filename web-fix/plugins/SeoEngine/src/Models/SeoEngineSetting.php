<?php

namespace App\Plugins\SeoEngine\Models;

use Illuminate\Database\Eloquent\Model;

class SeoEngineSetting extends Model
{
    protected $table = 'seo_engine_settings';

    protected $fillable = ['key', 'value', 'group'];

    protected $casts = [
        'value' => 'array',
    ];
}
