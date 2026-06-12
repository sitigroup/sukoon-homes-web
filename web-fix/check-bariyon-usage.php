<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

foreach ([2, 59] as $subId) {
    echo "=== sub_area_id {$subId} usage ===\n";
    $props = DB::table('area_listing_property_locations')->where('sub_area_id', $subId)->count();
    $projs = DB::table('area_listing_project_locations')->where('sub_area_id', $subId)->count();
    echo "properties={$props} projects={$projs}\n";
}

echo "\n=== Similar normalized names in Barmer ===\n";
$rows = DB::table('area_listing_sub_areas as s')
    ->join('area_listing_areas as a', 'a.id', '=', 's.area_id')
    ->where('a.city', 'Barmer')
    ->where(function ($q) {
        $q->whereRaw('LOWER(TRIM(s.normalized_name)) LIKE ?', ['%bariyon%'])
            ->orWhereRaw('LOWER(TRIM(s.name)) LIKE ?', ['%bariyon%']);
    })
    ->get(['s.id', 's.name', 's.normalized_name', 's.area_id', 'a.name as area_name']);

foreach ($rows as $row) {
    echo json_encode($row) . "\n";
}

echo "\n=== Pending suggestions bariyon ===\n";
$pending = DB::table('area_listing_suggestions')
    ->whereRaw('LOWER(TRIM(normalized_name)) LIKE ?', ['%bariyon%'])
    ->get(['id', 'type', 'name', 'normalized_name', 'area_id', 'status']);
foreach ($pending as $row) {
    echo json_encode($row) . "\n";
}
