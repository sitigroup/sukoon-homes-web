<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$id = (int) ($argv[1] ?? 18);
$loc = DB::table('area_listing_property_locations')->where('property_id', $id)->first();
$prop = DB::table('propertys')->where('id', $id)->first(['city', 'state']);
$city = $loc && $loc->city_id ? DB::table('area_listing_cities')->where('id', $loc->city_id)->value('name') : null;

echo "property city: {$prop->city}\n";
echo "location city_id: " . ($loc->city_id ?? 'null') . " stored city name: " . ($loc->city ?? '') . "\n";
echo "master city name: {$city}\n";
echo "detected_area: " . ($loc->detected_area_name ?? '') . "\n";
echo "detected_sub: " . ($loc->detected_sub_area_name ?? '') . "\n";
