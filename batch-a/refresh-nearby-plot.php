<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use Illuminate\Support\Facades\DB;

$p = Property::query()
    ->where('title', 'like', '%plot%barmer%')
    ->orWhere('title', 'like', '%Plot in barmer%')
    ->orderByDesc('id')
    ->first();
if (! $p) {
    $p = Property::query()->where('title', 'like', '%barmer%')->orderByDesc('id')->first();
}
if (! $p) {
    echo "property not found\n";
    exit(1);
}
echo 'title=' . $p->title . "\n";

echo 'id=' . $p->id . "\n";
$loc = DB::table('area_listing_property_locations')->where('property_id', $p->id)->first();
if ($loc) {
    echo 'area_id=' . ($loc->area_id ?? '') . ' sub_area_id=' . ($loc->sub_area_id ?? '') . "\n";
    echo 'area_name=' . ($loc->area_name ?? '') . ' sub_area_name=' . ($loc->sub_area_name ?? '') . "\n";
    if ($loc->area_id && class_exists(\App\Plugins\AreaListing\Models\Area::class)) {
        $a = \App\Plugins\AreaListing\Models\Area::find($loc->area_id);
        if ($a) {
            echo 'area center=' . ($a->center_lat ?? 'null') . ',' . ($a->center_lng ?? 'null') . "\n";
        }
    }
    if ($loc->sub_area_id && class_exists(\App\Plugins\AreaListing\Models\SubArea::class)) {
        $s = \App\Plugins\AreaListing\Models\SubArea::find($loc->sub_area_id);
        if ($s) {
            echo 'sub_area center=' . ($s->center_lat ?? 'null') . ',' . ($s->center_lng ?? 'null') . "\n";
        }
    }
}

$svc = app(\App\Plugins\NearbyPlaces\Services\NearbyPlacesService::class);
$cleared = $svc->clearCacheForProperty($p->id);
echo 'cleared=' . json_encode($cleared) . "\n";
$result = $svc->getForProperty($p->id, true);
echo 'area_label=' . ($result['area_label'] ?? '') . "\n";
echo 'places_count=' . count($result['places'] ?? []) . "\n";
if (! empty($result['places'][0]['name'])) {
    echo 'first_place=' . $result['places'][0]['name'] . "\n";
}
