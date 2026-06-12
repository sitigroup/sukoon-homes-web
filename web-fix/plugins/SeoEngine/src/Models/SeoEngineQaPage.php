<?php

namespace App\Plugins\SeoEngine\Models;

use Illuminate\Database\Eloquent\Model;

class SeoEngineQaPage extends Model
{
    protected $table = 'seo_engine_qa_pages';

    protected $fillable = [
        'slug',
        'question',
        'direct_answer',
        'body_html',
        'category',
        'related_rent_links',
        'status',
    ];

    protected $casts = [
        'related_rent_links' => 'array',
    ];

    public function publicPath(): string
    {
        return '/guides/' . $this->category . '/' . $this->slug . '/';
    }
}
