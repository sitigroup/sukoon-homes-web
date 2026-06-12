<?php

namespace App\Plugins\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaEventMap extends Model
{
    protected $table = 'wa_event_map';

    protected $guarded = [];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(WaTemplate::class, 'template_id');
    }
}

