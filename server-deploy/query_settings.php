<?php

declare(strict_types=1);

require '/www/wwwroot/admin-homes/vendor/autoload.php';

$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = Illuminate\Support\Facades\DB::table('settings')
    ->whereIn('type', ['nearby_places_travel_time_enabled', 'nearby_places_travel_cache_ttl_hours'])
    ->orderBy('type')
    ->get(['type', 'data']);

foreach ($rows as $r) {
    echo $r->type . '=' . $r->data . PHP_EOL;
}
