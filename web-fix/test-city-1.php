<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (['city', 'cities', 'tbl_city'] as $table) {
    try {
        $row = DB::table($table)->where('id', 1)->first();
        if ($row) {
            echo "table={$table} id=1: " . json_encode($row) . "\n";
        }
    } catch (Throwable $e) {
        // skip
    }
}

$area = DB::table('area_listing_areas')->where('id', 45)->first();
echo 'area 45: ' . json_encode($area) . "\n";
