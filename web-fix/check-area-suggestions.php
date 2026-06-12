<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo json_encode([
    'pending' => DB::table('area_listing_suggestions')->where('status', 'pending')->count(),
    'recent_suggestions' => DB::table('area_listing_suggestions')->orderByDesc('id')->limit(5)->get(['id', 'type', 'name', 'city', 'status', 'suggested_by', 'created_at']),
    'recent_locations' => DB::table('area_listing_property_locations')->orderByDesc('id')->limit(5)->get(),
], JSON_PRETTY_PRINT) . PHP_EOL;
