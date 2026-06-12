<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo 'mangi areas: ' . DB::table('area_listing_areas')->where('normalized_name', 'mangi')->count() . "\n";
echo 'temple subs: ' . DB::table('area_listing_sub_areas')->where('normalized_name', 'temple')->count() . "\n";

$s55 = DB::table('area_listing_suggestions')->where('id', 55)->first();
echo 'suggestion 55: ' . json_encode($s55) . "\n";
