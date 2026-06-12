<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$loc = DB::table('area_listing_project_locations')->where('project_id', 7)->first();
echo json_encode($loc, JSON_PRETTY_PRINT) . "\n";

$p = DB::table('projects')->where('id', 7)->first(['id','title','location','request_status']);
echo json_encode($p) . "\n";
