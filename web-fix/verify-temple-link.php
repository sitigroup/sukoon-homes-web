<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$loc = DB::table('area_listing_project_locations')->where('project_id', 7)->first();
echo "project7: area_id={$loc->area_id} sub_id={$loc->sub_area_id} sub_name={$loc->sub_area_name}\n";

$sub = DB::table('area_listing_sub_areas')->where('id', $loc->sub_area_id)->first();
echo "sub record: " . json_encode($sub) . "\n";

$temple = DB::table('area_listing_sub_areas')->where('area_id', 59)->where('normalized_name', 'temple')->first();
echo "temple under 59: " . json_encode($temple) . "\n";

if ($temple && (int) $loc->sub_area_id !== (int) $temple->id) {
    DB::table('area_listing_project_locations')->where('project_id', 7)->update([
        'sub_area_id' => $temple->id,
        'sub_area_name' => $temple->name,
        'updated_at' => now(),
    ]);
    echo "fixed project 7 sub_area_id to {$temple->id}\n";
}
