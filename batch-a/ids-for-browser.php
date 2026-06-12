<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$a = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$p = DB::table('propertys')->orderByDesc('id')->first(['id', 'title', 'city']);
if (! $p) {
    $p = DB::table('properties')->orderByDesc('id')->first(['id', 'title', 'city']);
}
$s = DB::table('area_listing_sub_areas')->where('status', 1)->orderByDesc('id')->first(['id', 'name', 'area_id']);
$loc = DB::table('area_listing_property_locations')->whereNotNull('area_id')->orderByDesc('property_id')->first();
echo 'property=' . json_encode($p) . PHP_EOL;
echo 'sub=' . json_encode($s) . PHP_EOL;
echo 'loc=' . json_encode($loc) . PHP_EOL;
$proj = DB::table('projects')->orderByDesc('id')->first(['id', 'title']);
echo 'project=' . json_encode($proj) . PHP_EOL;
