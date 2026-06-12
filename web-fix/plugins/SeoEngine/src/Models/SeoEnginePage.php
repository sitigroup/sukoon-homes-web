<?php

namespace App\Plugins\SeoEngine\Models;

use Illuminate\Database\Eloquent\Model;

class SeoEnginePage extends Model
{
    protected $table = 'seo_engine_pages';

    protected $fillable = [
        'path',
        'page_type',
        'params',
        'title',
        'h1',
        'meta_description',
        'intro_html',
        'faq_json',
        'listing_count',
        'quality_score',
        'is_indexable',
        'lock_content',
        'content_generated_at',
    ];

    protected $casts = [
        'params' => 'array',
        'faq_json' => 'array',
        'is_indexable' => 'boolean',
        'lock_content' => 'boolean',
        'content_generated_at' => 'datetime',
    ];
}
