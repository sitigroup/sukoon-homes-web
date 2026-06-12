<?php
$adminRoot = is_file(__DIR__ . '/vendor/autoload.php') ? __DIR__ : dirname(__DIR__);
require $adminRoot . '/vendor/autoload.php';
$app = require $adminRoot . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$id = (int) ($argv[1] ?? 67);
$customerId = (int) ($argv[2] ?? 15);

echo json_encode([
    'area' => DB::table('area_listing_areas')->where('id', $id)->first(),
    'sub_area' => DB::table('area_listing_sub_areas')->where('id', $id)->first(),
    'customer' => DB::table('customers')->where('id', $customerId)->first(['id', 'name', 'is_agent', 'can_manage_area_listing']),
    'location_48' => DB::table('area_listing_property_locations')->where('id', 48)->first(),
    'pending_count' => DB::table('area_listing_suggestions')->where('status', 'pending')->count(),
    'areas_baldev' => DB::table('area_listing_areas')->where('name', 'like', '%Baldev%')->get(['id', 'name', 'city', 'city_name', 'workflow_status', 'status']),
    'sub_gali' => DB::table('area_listing_sub_areas')->where('name', 'like', '%Gali%')->get(['id', 'name', 'area_id', 'workflow_status', 'status']),
], JSON_PRETTY_PRINT) . PHP_EOL;
