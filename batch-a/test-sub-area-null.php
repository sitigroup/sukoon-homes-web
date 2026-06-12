<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Plugins\AreaListing\Services\AreaListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$property = Property::query()->orderByDesc('id')->first();
if (! $property) {
    echo "no property\n";
    exit(1);
}

$area = DB::table('area_listing_areas')->where('status', 1)->where('name', 'like', '%Baldev%')->first()
    ?? DB::table('area_listing_areas')->where('status', 1)->orderByDesc('id')->first();

if (! $area) {
    echo "no area\n";
    exit(1);
}

$req = Request::create('/', 'POST', [
    'area_id' => $area->id,
    'sub_area_id' => '',
    'detected_sub_area_name' => 'Rai Colony',
    'detected_area_name' => $area->name,
    'city' => $area->city_name ?: $area->city,
    'state' => $area->state,
    'country' => $area->country ?: 'India',
    'latitude' => '25.75',
    'longitude' => '71.38',
]);

AreaListingService::savePropertyLocation($property, $req);
$row = DB::table('area_listing_property_locations')->where('property_id', $property->id)->first();

echo 'area_id=' . ($row->area_id ?? 'null') . PHP_EOL;
echo 'sub_area_id=' . var_export($row->sub_area_id ?? null, true) . PHP_EOL;
echo 'detected_sub_area_name=' . var_export($row->detected_sub_area_name ?? null, true) . PHP_EOL;
echo ($row && empty($row->sub_area_id)) ? "PASS\n" : "FAIL\n";
