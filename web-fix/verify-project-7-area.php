<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$l = DB::table('area_listing_project_locations')->where('project_id', 7)->first();
echo "project 7 area_id={$l->area_id} sub_area_id={$l->sub_area_id}\n";
echo "area_name={$l->area_name} sub_area_name={$l->sub_area_name}\n";
echo "pending suggestions: " . DB::table('area_listing_suggestions')->where('status', 'pending')->count() . "\n";
