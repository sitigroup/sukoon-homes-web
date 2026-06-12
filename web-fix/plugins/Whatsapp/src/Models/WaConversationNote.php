<?php

namespace App\Plugins\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class WaConversationNote extends Model
{
    protected $table = 'wa_conversation_notes';

    protected $fillable = [
        'conversation_id',
        'author_id',
        'body',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WaConversation::class, 'conversation_id');
    }

    public function authorLabel(): string
    {
        if (! $this->author_id) {
            return __('whatsapp::whatsapp.note_author_unknown');
        }

        $name = DB::table('users')->where('id', $this->author_id)->value('name');
        if ($name) {
            return (string) $name;
        }

        return __('whatsapp::whatsapp.note_author_staff', ['id' => $this->author_id]);
    }
}
