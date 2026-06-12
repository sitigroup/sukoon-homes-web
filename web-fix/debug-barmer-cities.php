<?php
$adminRoot = is_file(__DIR__ . '/vendor/autoload.php') ? __DIR__ : dirname(__DIR__);
require $adminRoot . '/vendor/autoload.php';
$app = require $adminRoot . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo json_encode([
    'barmer_cities' => DB::table('area_listing_cities')->where('state', 'Rajasthan')->where('name', 'like', '%Barmer%')->get(['id', 'name', 'state']),
    'area_67_visible_under_barmer' => DB::table('area_listing_areas')->where('id', 67)->where(function ($q) {
        $cityIds = DB::table('area_listing_cities')->where('name', 'Barmer')->where('state', 'Rajasthan')->pluck('id');
        $q->whereIn('city_id', $cityIds)->orWhere(function ($f) {
            $f->whereNull('city_id')->where('city_name', 'Barmer');
        });
    })->exists(),
], JSON_PRETTY_PRINT) . PHP_EOL;
