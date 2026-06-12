<?php

namespace App\Plugins\SeoEngine\Models;

use Illuminate\Database\Eloquent\Model;

class SeoEngineRedirect extends Model
{
    protected $table = 'seo_engine_redirects';

    protected $fillable = ['from_path', 'to_path', 'status_code', 'hits'];
}
