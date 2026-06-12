<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use Illuminate\Support\Facades\DB;

$id = 11;
$p = Property::find($id);
if (! $p) {
    echo "property not found\n";
    exit(1);
}

echo "=== propertys table ===\n";
echo "title={$p->title}\n";
echo "city={$p->city} state={$p->state} country={$p->country}\n";
echo "address={$p->address}\n";
echo "lat={$p->latitude} lng={$p->longitude}\n";

$loc = DB::table('area_listing_property_locations')->where('property_id', $id)->first();
echo "\n=== area_listing_property_locations ===\n";
if ($loc) {
    foreach ((array) $loc as $k => $v) {
        if ($v !== null && $v !== '') {
            echo "$k=$v\n";
        }
    }
} else {
    echo "no row\n";
}

if ($loc && $loc->area_id) {
    $a = \App\Plugins\AreaListing\Models\Area::find($loc->area_id);
    if ($a) {
        echo "\n=== linked area ===\n";
        echo "id={$a->id} name={$a->name} city_id={$a->city_id} city_name=" . ($a->city_name ?? $a->city ?? '') . "\n";
        echo "center={$a->center_lat},{$a->center_lng}\n";
    }
}
if ($loc && $loc->sub_area_id) {
    $s = \App\Plugins\AreaListing\Models\SubArea::find($loc->sub_area_id);
    if ($s) {
        echo "\n=== linked sub_area ===\n";
        echo "id={$s->id} name={$s->name} area_id={$s->area_id}\n";
    }
}

$cacheCount = DB::table('nearby_place_caches')->where('property_id', $id)->count();
echo "\nnearby_place_caches count=$cacheCount\n";
if ($cacheCount > 0) {
    $sample = DB::table('nearby_place_caches')->where('property_id', $id)->limit(3)->get(['name', 'latitude', 'longitude']);
    foreach ($sample as $row) {
        echo "  place: {$row->name} @ {$row->latitude},{$row->longitude}\n";
    }
}

require_once '/www/wwwroot/admin-homes/app/Helpers/custom_helper.php';
$row = $p->toArray();
$guest = \App\Plugins\AreaListing\Services\AreaListingService::decoratePropertyResponse($row, $p, null);
echo "\n=== public API label ===\n";
echo "address=" . ($guest['address'] ?? '') . "\n";
echo "area=" . ($guest['area_listing']['area_name'] ?? '') . " sub=" . ($guest['area_listing']['sub_area_name'] ?? '') . "\n";
