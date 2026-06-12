<?php

namespace App\Plugins\Whatsapp\Models;

use Illuminate\Database\Eloquent\Model;

class WaBatchReminderLog extends Model
{
    protected $table = 'wa_batch_reminder_logs';

    protected $guarded = [];

    protected $casts = [
        'result_json' => 'array',
    ];
}
