<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo 'properties=' . App\Models\Property::count() . "\n";
echo 'property_locations=' . Illuminate\Support\Facades\DB::table('area_listing_property_locations')->count() . "\n";
echo 'with_area_id=' . Illuminate\Support\Facades\DB::table('area_listing_property_locations')->whereNotNull('area_id')->where('area_id', '>', 0)->count() . "\n";
