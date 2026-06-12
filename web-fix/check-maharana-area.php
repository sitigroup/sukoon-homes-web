<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$row = DB::table('area_listing_areas')
    ->where('name', 'like', '%Maharana%')
    ->first(['id', 'name', 'city_id', 'city_name', 'city', 'state', 'country']);

echo json_encode($row, JSON_PRETTY_PRINT) . PHP_EOL;

$udaipur = DB::table('area_listing_cities')
    ->where('name', 'like', '%Udaipur%')
    ->get(['id', 'name', 'state', 'country']);

echo "Udaipur cities:\n" . json_encode($udaipur, JSON_PRETTY_PRINT) . PHP_EOL;
