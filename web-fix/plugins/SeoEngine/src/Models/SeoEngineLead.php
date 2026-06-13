<?php

namespace App\Plugins\SeoEngine\Models;

use Illuminate\Database\Eloquent\Model;

class SeoEngineLead extends Model
{
    protected $table = 'seo_engine_leads';

    protected $fillable = [
        'name',
        'phone',
        'requirement',
        'source_path',
        'area_id',
        'sub_area_id',
        'form_type',
        'status',
        'ip_hash',
    ];
}
