<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$updated = DB::table('area_listing_areas')
    ->where('id', 43)
    ->whereNull('city_id')
    ->update(['city_id' => 10, 'updated_at' => now()]);

echo "updated={$updated}\n";

$row = DB::table('area_listing_areas')->where('id', 43)->first(['id', 'name', 'city_id', 'city_name']);
echo json_encode($row) . PHP_EOL;
