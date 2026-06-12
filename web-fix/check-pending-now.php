<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

echo "=== Pending suggestions ===\n";
foreach (DB::table('area_listing_suggestions')->where('status', 'pending')->orderByDesc('id')->limit(8)->get() as $s) {
    echo json_encode($s) . "\n";
}

echo "\n=== Latest project locations ===\n";
foreach (DB::table('area_listing_project_locations')->orderByDesc('updated_at')->limit(3)->get() as $l) {
    echo json_encode($l) . "\n";
}
