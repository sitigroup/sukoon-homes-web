<?php

namespace App\Plugins\Whatsapp\Models;

use App\Plugins\Whatsapp\Support\MetaApiErrorFormatter;
use App\Plugins\Whatsapp\Support\WaTemplateRenderHelper;
use Illuminate\Database\Eloquent\Model;

class WaMessage extends Model
{
    protected $table = 'wa_messages';

    protected $guarded = [];

    protected $casts = [
        'error_json' => 'array',
    ];

    public function metaErrorSummary(): ?string
    {
        return MetaApiErrorFormatter::summarize($this->error_json);
    }

    public function displayBody(): string
    {
        return WaTemplateRenderHelper::renderForMessage($this);
    }

    public function contact()
    {
        return $this->belongsTo(WaContact::class, 'contact_id');
    }
}
