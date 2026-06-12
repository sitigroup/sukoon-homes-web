<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo 'projects=' . App\Models\Projects::count() . "\n";
echo 'areas=' . Illuminate\Support\Facades\DB::table('area_listing_areas')->count() . "\n";
echo 'loc_rows=' . Illuminate\Support\Facades\DB::table('area_listing_project_locations')->count() . "\n";
