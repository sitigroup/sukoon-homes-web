<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$c = DB::table('customers')->where('id', 15)->first(['id', 'name', 'is_agent', 'can_manage_area_listing']);
$a67 = DB::table('area_listing_areas')->where('id', 67)->first();
$s67 = DB::table('area_listing_sub_areas')->where('id', 67)->first();
$recentAreas = DB::table('area_listing_areas')->orderByDesc('id')->limit(5)->get(['id', 'name', 'city', 'workflow_status', 'status', 'created_at']);

echo json_encode([
    'customer_15' => $c,
    'area_67' => $a67,
    'sub_67' => $s67,
    'recent_areas' => $recentAreas,
], JSON_PRETTY_PRINT) . PHP_EOL;
