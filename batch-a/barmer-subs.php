<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$a = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rows = DB::table('area_listing_sub_areas as s')
    ->join('area_listing_areas as a', 'a.id', '=', 's.area_id')
    ->where('a.city_name', 'Barmer')->orWhere('a.city', 'Barmer')
    ->select('s.id', 's.name', 's.area_id', 'a.name as area_name')
    ->get();
foreach ($rows as $r) {
    echo json_encode($r) . PHP_EOL;
}
