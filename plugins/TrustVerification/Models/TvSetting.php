<?php

namespace App\Plugins\TrustVerification\Models;

use Illuminate\Database\Eloquent\Model;

class TvSetting extends Model
{
    protected $table = 'tv_settings';

    protected $fillable = ['key', 'value'];
}
