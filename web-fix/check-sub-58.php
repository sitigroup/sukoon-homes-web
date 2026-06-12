<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$area = DB::table('area_listing_areas')->where('id', 54)->first();
echo "area 54: {$area->name}\n";

$subs = DB::table('area_listing_sub_areas')->where('area_id', 54)->get();
echo "subs under 54: " . $subs->count() . "\n";
foreach ($subs as $s) {
    echo "  {$s->id} {$s->name}\n";
}

$match = DB::table('area_listing_sub_areas')
    ->where('area_id', 54)
    ->where('normalized_name', 'agrawalon ka mohalla road')
    ->first();
echo "exact match: " . json_encode($match) . "\n";

$s58 = DB::table('area_listing_suggestions')->where('id', 58)->first();
echo "suggestion 58: " . json_encode($s58) . "\n";
