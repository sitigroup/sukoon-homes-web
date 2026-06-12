<?php

namespace App\Plugins\SeoEngine\Models;

use Illuminate\Database\Eloquent\Model;

class SeoEngineContentFailure extends Model
{
    protected $table = 'seo_engine_content_failures';

    protected $fillable = ['path', 'provider', 'error'];
}
