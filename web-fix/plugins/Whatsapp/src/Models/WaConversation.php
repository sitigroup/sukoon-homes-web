<?php

namespace App\Plugins\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaConversation extends Model
{
    protected $table = 'wa_conversations';

    protected $guarded = [];

        protected $casts = [
        'last_customer_message_at' => 'datetime',
        'conversation_window_expires_at' => 'datetime',
        'last_message_at' => 'datetime',
        'off_hours_auto_reply_at' => 'datetime',
        'keyword_auto_reply_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(WaMessage::class, 'conversation_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(WaContact::class, 'contact_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(WaConversationNote::class, 'conversation_id');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(WaConversationTag::class, 'conversation_id');
    }
}

