<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$settings = DB::table('wa_settings')->latest('id')->first();

echo json_encode([
    'out_ok_7d' => DB::table('wa_messages')
        ->where('direction', 'out')
        ->whereIn('status', ['sent', 'delivered', 'read'])
        ->where('created_at', '>=', now()->subDays(7))
        ->count(),
    'delivered_7d' => DB::table('wa_messages')
        ->where('direction', 'out')
        ->where('status', 'delivered')
        ->where('created_at', '>=', now()->subDays(7))
        ->count(),
    'keyword_automation_enabled' => (bool) ($settings->keyword_automation_enabled ?? false),
    'off_hours_enabled' => (bool) ($settings->off_hours_enabled ?? false),
    'last_inbound' => DB::table('wa_messages')->where('direction', 'in')->max('created_at'),
], JSON_PRETTY_PRINT) . PHP_EOL;
