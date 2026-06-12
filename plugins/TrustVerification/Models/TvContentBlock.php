<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;

class TvContentBlock extends Model
{
    public const TYPE_TEXT = 'text';

    public const TYPE_HTML = 'html';

    public const TYPE_JSON = 'json';

    public const TYPE_FAQ = 'faq';

    public const TYPE_LEGAL = 'legal';

    public const TYPE_EMAIL = 'email';

    public const TYPE_REPORT = 'report';

    public const TYPE_TESTIMONIAL = 'testimonial';

    protected $table = 'tv_content_blocks';

    protected $fillable = [
        'content_key',
        'group_key',
        'title',
        'type',
        'content',
        'content_json',
        'is_active',
        'sort_order',
        'version',
        'updated_by',
    ];

    protected $casts = [
        'content_json' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'version' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInGroup($query, string $group)
    {
        return $query->where('group_key', $group);
    }
}
