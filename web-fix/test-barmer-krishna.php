<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Services\AreaListingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$components = [
    ['name' => '1047', 'types' => ['premise']],
    ['name' => 'krishna Nagar', 'types' => ['political', 'sublocality', 'sublocality_level_1']],
    ['name' => 'Barmer', 'types' => ['locality', 'political']],
    ['name' => 'Barmer', 'types' => ['administrative_area_level_3', 'political']],
    ['name' => 'Jodhpur Division', 'types' => ['administrative_area_level_2', 'political']],
    ['name' => 'Rajasthan', 'types' => ['administrative_area_level_1', 'political']],
    ['name' => 'India', 'types' => ['country', 'political']],
    ['name' => '344001', 'types' => ['postal_code']],
];

$lat = 25.7522359;
$lng = 71.3919102;

$cityTable = Schema::hasTable('city') ? 'city' : (Schema::hasTable('cities') ? 'cities' : null);
echo "city_table={$cityTable}\n";

if ($cityTable) {
    $cities = DB::table($cityTable)->where('name', 'like', '%Barmer%')->limit(10)->get();
    echo "=== Cities Barmer ===\n";
    foreach ($cities as $c) {
        echo json_encode($c) . "\n";
    }
}

echo "\n=== Areas Krishna Nagar ===\n";
$areas = DB::table('area_listing_areas')
    ->where('workflow_status', 'active')
    ->whereRaw('LOWER(name) LIKE ?', ['%krishna nagar%'])
    ->get();
foreach ($areas as $a) {
    $cityName = $cityTable ? DB::table($cityTable)->where('id', $a->city_id)->value('name') : '?';
    echo "area_id={$a->id} name={$a->name} city_id={$a->city_id} city={$cityName}\n";
    $subs = DB::table('area_listing_sub_areas')
        ->where('area_id', $a->id)
        ->where('workflow_status', 'active')
        ->get(['id', 'name']);
    echo "  active_sub_areas={$subs->count()}\n";
    foreach ($subs as $s) {
        echo "    {$s->id}: {$s->name}\n";
    }
}

echo "\n=== resolveNearestFromCoordinates ===\n";
$result = AreaListingService::resolveNearestFromCoordinates($lat, $lng, null, 'Barmer', 'Rajasthan', 'India', $components);
echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
