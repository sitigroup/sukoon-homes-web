<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rows = DB::table('area_listing_areas')->select('id', 'status', 'city', 'city_name', 'state')->get();
foreach ($rows as $r) {
    echo json_encode($r) . "\n";
}
