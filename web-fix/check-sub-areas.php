<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$areaId = (int) ($argv[1] ?? 0);
if ($areaId <= 0) {
    $sample = DB::table('area_listing_areas')
        ->where('workflow_status', 'active')
        ->orderByDesc('id')
        ->value('id');
    echo "Usage: php check-sub-areas.php AREA_ID\n";
    echo "Sample active area_id (latest): {$sample}\n";
    exit(1);
}

$subs = DB::table('area_listing_sub_areas')
    ->where('area_id', $areaId)
    ->where('workflow_status', 'active')
    ->get(['id', 'name', 'area_id']);

echo "area_id={$areaId} active_sub_areas=" . $subs->count() . "\n";
foreach ($subs as $row) {
    echo json_encode($row) . "\n";
}
