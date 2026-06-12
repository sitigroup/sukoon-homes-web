<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Services\AreaListingService;
use Illuminate\Http\Request;

// Simulate admin save: area_id set, sub_area_id empty, detected_sub_area_name present
$request = Request::create('/test', 'POST', [
    'area_listing_admin_save' => '1',
    'state' => 'Rajasthan',
    'city' => 'Udaipur',
    'country' => 'India',
    'city_id' => '10',
    'area_id' => '47',
    'sub_area_id' => '',
    'detected_area_name' => 'Khanjipeer',
    'detected_sub_area_name' => 'Test Sub Locality',
    'latitude' => '24.58',
    'longitude' => '73.71',
]);

$ref = new ReflectionMethod(AreaListingService::class, 'saveListingLocation');
$ref->setAccessible(true);

// Use a throwaway property id - find max+1 or use 99999 and delete after
$pid = (int) DB::table('area_listing_property_locations')->max('property_id') + 9999;

try {
    $ref->invoke(null, 'area_listing_property_locations', 'property_id', $pid, $request);
    $row = DB::table('area_listing_property_locations')->where('property_id', $pid)->first();
    echo "TEST property_id={$pid}\n";
    echo "area_id={$row->area_id} sub_area_id={$row->sub_area_id}\n";
    echo "sub_area_name={$row->sub_area_name} detected_sub={$row->detected_sub_area_name}\n";
    $sub = DB::table('area_listing_sub_areas')->where('area_id', 47)->where('normalized_name', 'test sub locality')->first();
    echo "sub_in_db=" . ($sub ? $sub->id.' '.$sub->name : 'null') . "\n";
    DB::table('area_listing_property_locations')->where('property_id', $pid)->delete();
    if ($sub) {
        echo "left test sub in db id={$sub->id}\n";
    }
} catch (Throwable $e) {
    echo 'ERR: '.$e->getMessage()."\n";
}
