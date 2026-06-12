<?php

namespace App\Plugins\SeoEngine\Models;

use Illuminate\Database\Eloquent\Model;

class SeoEngineTemplateVersion extends Model
{
    protected $table = 'seo_engine_template_versions';

    protected $fillable = [
        'page_type',
        'title_template',
        'h1_template',
        'meta_description_template',
    ];
}
