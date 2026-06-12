<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$loc = DB::table('area_listing_property_locations as l')
    ->leftJoin('propertys as p', 'p.id', '=', 'l.property_id')
    ->where('l.sub_area_id', 59)
    ->first(['l.*', 'p.title', 'p.slug_id']);

echo json_encode($loc, JSON_PRETTY_PRINT) . "\n";
