<?php

namespace App\Plugins\Whatsapp\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WaCannedReply extends Model
{
    protected $table = 'wa_canned_replies';

    protected $fillable = [
        'title',
        'body_en',
        'body_hi',
        'sort_order',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    public function bodyForLocale(?string $locale = null): string
    {
        $locale = $locale ?: (app()->getLocale() === 'hi' ? 'hi' : 'en');
        if ($locale === 'hi' && filled($this->body_hi)) {
            return (string) $this->body_hi;
        }

        return (string) $this->body_en;
    }
}
