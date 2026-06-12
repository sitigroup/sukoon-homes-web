<?php

namespace App\Plugins\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaConversationTag extends Model
{
    protected $table = 'wa_conversation_tags';

    protected $fillable = [
        'conversation_id',
        'tag',
        'auto_applied',
    ];

    protected $casts = [
        'auto_applied' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WaConversation::class, 'conversation_id');
    }
}
