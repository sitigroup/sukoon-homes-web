<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Pending suggestions ===\n";
foreach (DB::table('area_listing_suggestions')->where('status', 'pending')->orderByDesc('id')->limit(15)->get() as $s) {
    echo json_encode($s) . "\n";
}

echo "\n=== Project 7 location ===\n";
$loc = DB::table('area_listing_project_locations')->where('project_id', 7)->first();
echo json_encode($loc) . "\n";

echo "\n=== Project 7 status ===\n";
$p = DB::table('projects')->where('id', 7)->first(['id', 'title', 'request_status']);
echo json_encode($p) . "\n";
