<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rows = DB::table('area_listing_property_locations')
    ->orderByDesc('updated_at')
    ->limit(10)
    ->get(['property_id', 'area_id', 'sub_area_id', 'detected_area_name', 'detected_sub_area_name', 'sub_area_name']);

foreach ($rows as $row) {
    echo json_encode($row, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
