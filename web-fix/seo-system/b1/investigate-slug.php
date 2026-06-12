<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$slug = '2-bhk-flat-for-rent';

$exact = Illuminate\Support\Facades\DB::table('propertys')
    ->where('slug_id', $slug)
    ->select('id', 'slug_id', 'title', 'status', 'propery_type', 'updated_at')
    ->first();

$like = Illuminate\Support\Facades\DB::table('propertys')
    ->where(function ($q) {
        $q->where('slug_id', 'like', '%2-bhk%')
            ->orWhere('slug_id', 'like', '%flat-for-rent%')
            ->orWhere('title', 'like', '%2 BHK%');
    })
    ->select('id', 'slug_id', 'title', 'status')
    ->limit(15)
    ->get();

echo "=== exact slug_id = {$slug} ===\n";
echo $exact ? json_encode($exact, JSON_PRETTY_PRINT) : "NOT FOUND\n";
echo "\n=== similar rows (max 15) ===\n";
foreach ($like as $row) {
    echo json_encode($row) . "\n";
}
