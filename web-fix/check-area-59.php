<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$a = DB::table('area_listing_areas')->where('id', 59)->first();
echo "area 59: " . json_encode($a) . "\n";

$props = DB::table('area_listing_property_locations')->where('area_id', 59)->orWhereRaw('LOWER(TRIM(detected_area_name)) = ?', ['mangi'])->orWhereRaw('LOWER(TRIM(area_name)) = ?', ['mangi'])->get();
echo "property locations: " . $props->count() . "\n";
foreach ($props as $p) {
    echo "  property {$p->property_id} area_id={$p->area_id} sub={$p->sub_area_name}\n";
}

$projs = DB::table('area_listing_project_locations')->where('area_id', 59)->orWhereRaw('LOWER(TRIM(detected_area_name)) = ?', ['mangi'])->orWhereRaw('LOWER(TRIM(area_name)) = ?', ['mangi'])->get();
echo "project locations: " . $projs->count() . "\n";
foreach ($projs as $p) {
    echo json_encode($p) . "\n";
}

$loc7 = DB::table('area_listing_project_locations')->where('project_id', 7)->first();
echo "project 7 full: " . json_encode($loc7) . "\n";

$s = DB::table('area_listing_suggestions')->where('normalized_name', 'mangi')->orderByDesc('id')->limit(3)->get();
echo "suggestions:\n";
foreach ($s as $row) {
    echo "  id={$row->id} status={$row->status} note={$row->review_note}\n";
}
