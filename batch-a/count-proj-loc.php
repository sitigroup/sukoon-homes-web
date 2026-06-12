<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$a = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$a->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo 'count=' . DB::table('area_listing_project_locations')->count() . PHP_EOL;
$r = DB::table('area_listing_project_locations')->orderByDesc('project_id')->first();
print_r($r);
