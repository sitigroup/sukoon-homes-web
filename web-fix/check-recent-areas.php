<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Recent areas (last 10) ===\n";
foreach (DB::table('area_listing_areas')->orderByDesc('id')->limit(10)->get() as $a) {
    echo json_encode($a) . "\n";
}

echo "\n=== Recent sub_areas (last 10) ===\n";
foreach (DB::table('area_listing_sub_areas')->orderByDesc('id')->limit(10)->get() as $s) {
    echo json_encode($s) . "\n";
}

echo "\n=== Recently approved suggestions ===\n";
foreach (DB::table('area_listing_suggestions')->where('status', 'approved')->orderByDesc('reviewed_at')->limit(8)->get() as $s) {
    echo json_encode($s) . "\n";
}
