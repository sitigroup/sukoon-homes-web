<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Plugins\AreaListing\Models\City;
use Illuminate\Support\Facades\DB;

$cities = City::query()->where('status', true)->orderBy('name')->get();
foreach ($cities as $city) {
    $skip = false;
    foreach ($cities as $other) {
        if ((int) $other->id === (int) $city->id) continue;
        $suffix = '-' . $other->slug;
        if ($suffix !== '-' && str_ends_with($city->slug, $suffix)) $skip = true;
    }
    $hasLocs = DB::table('area_listing_property_locations as apl')
        ->join('propertys as p', 'p.id', '=', 'apl.property_id')
        ->where('apl.city_id', $city->id)
        ->where('p.status', 1)->where('p.request_status', 'approved')->where('p.propery_type', 1)
        ->exists();
    echo "City model id={$city->id} slug={$city->slug} skip={$skip} locs={$hasLocs} gen=" . (!$skip && $hasLocs ? 'Y' : 'N') . "\n";
}

echo "\nListing query prop 34:\n";
$row = Property::query()
    ->select([DB::raw('COALESCE(lc.name, propertys.city) as city'), 'propertys.id', 'lc.name as lc_name', 'propertys.city as p_city'])
    ->join('area_listing_property_locations as apl', 'apl.property_id', '=', 'propertys.id')
    ->leftJoin('area_listing_cities as lc', 'lc.id', '=', 'apl.city_id')
    ->where('propertys.id', 34)
    ->first();
print_r($row?->toArray());
