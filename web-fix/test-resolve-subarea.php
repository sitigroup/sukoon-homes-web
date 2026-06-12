<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Services\AreaListingService;
use Illuminate\Support\Facades\DB;

$areaId = (int) ($argv[1] ?? 0);
$lat = (float) ($argv[2] ?? 0);
$lng = (float) ($argv[3] ?? 0);
$city = (string) ($argv[4] ?? 'Udaipur');
$state = (string) ($argv[5] ?? 'Rajasthan');

if ($areaId <= 0) {
    $row = DB::table('area_listing_areas')->where('workflow_status', 'active')->orderByDesc('id')->first();
    $areaId = (int) ($row->id ?? 0);
    echo "Using latest area_id={$areaId} name=" . ($row->name ?? '') . " city_id=" . ($row->city_id ?? '') . "\n";
}

$subs = DB::table('area_listing_sub_areas')
    ->where('area_id', $areaId)
    ->where('workflow_status', 'active')
    ->get(['id', 'name']);

echo "DB sub_areas for area {$areaId}: " . $subs->count() . "\n";
foreach ($subs as $s) {
    echo "  - {$s->id}: {$s->name}\n";
}

$area = DB::table('area_listing_areas')->where('id', $areaId)->first();
$cityId = $area->city_id ?? null;

$components = [
    ['name' => $area->name ?? 'Test Area', 'types' => ['sublocality_level_1']],
];
if ($subs->isNotEmpty()) {
    $components[] = ['name' => $subs->first()->name, 'types' => ['sublocality_level_2']];
}

$result = AreaListingService::resolveNearestFromCoordinates(
    $lat ?: 24.5854,
    $lng ?: 73.7147,
    $cityId ? (int) $cityId : null,
    $city,
    $state,
    'India',
    $components
);

echo "resolveNearestFromCoordinates JSON:\n";
echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
