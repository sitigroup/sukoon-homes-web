<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use Illuminate\Support\Facades\DB;
use App\Plugins\AreaListing\Services\AreaListingService;

$id = 11;
$loc = DB::table('area_listing_property_locations')->where('property_id', $id)->first();
if (! $loc) {
    echo "no location row\n";
    exit(1);
}

$sub = $loc->sub_area_id ? \App\Plugins\AreaListing\Models\SubArea::find($loc->sub_area_id) : null;
$mismatch = $sub && (int) $sub->area_id !== (int) $loc->area_id;

if ($mismatch) {
    $area = \App\Plugins\AreaListing\Models\Area::find($loc->area_id);
    $areaName = $area->name ?? $loc->area_name;
    $display = implode(', ', array_filter([$areaName, $loc->city ?? null, $loc->state ?? null]));
    DB::table('area_listing_property_locations')->where('property_id', $id)->update([
        'sub_area_id' => null,
        'sub_area_name' => null,
        'detected_sub_area_name' => null,
        'display_address' => $display,
        'updated_at' => now(),
    ]);
    echo "cleared mismatched sub_area_id={$loc->sub_area_id} (was for area {$sub->area_id}, listing area {$loc->area_id})\n";
} else {
    echo "no mismatch\n";
}

if (class_exists(\App\Plugins\NearbyPlaces\Services\NearbyPlacesService::class)) {
    $cleared = app(\App\Plugins\NearbyPlaces\Services\NearbyPlacesService::class)->clearCacheForProperty($id);
    echo 'nearby_cleared=' . json_encode($cleared) . "\n";
    $p = Property::find($id);
    $refreshed = app(\App\Plugins\NearbyPlaces\Services\NearbyPlacesService::class)->getForProperty($id, true);
    echo 'nearby_label=' . ($refreshed['area_label'] ?? '') . ' places=' . count($refreshed['places'] ?? []) . "\n";
}

require_once '/www/wwwroot/admin-homes/app/Helpers/custom_helper.php';
$p = Property::find($id);
$guest = \App\Plugins\AreaListing\Services\AreaListingService::decoratePropertyResponse($p->toArray(), $p, null);
echo 'public_address=' . ($guest['address'] ?? '') . "\n";
