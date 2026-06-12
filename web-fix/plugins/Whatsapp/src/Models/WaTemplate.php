<?php

namespace App\Plugins\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;

class WaTemplate extends Model
{
    protected $table = 'wa_templates';

    protected $guarded = [];

    protected $casts = [
        'components_json' => 'array',
        'enabled' => 'boolean',
    ];
}

