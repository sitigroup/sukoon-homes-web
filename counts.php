<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$a=require '/www/wwwroot/admin-homes/bootstrap/app.php';
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo 'projects='.DB::table('projects')->count()."\n";
echo 'proj_loc='.DB::table('area_listing_project_locations')->count()."\n";
echo 'proj_with_area='.DB::table('area_listing_project_locations')->whereNotNull('area_id')->where('area_id','>',0)->count()."\n";
