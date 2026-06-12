<?php
$base = '/www/wwwroot/admin-homes';
chdir($base);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ownerId = (int) ($argv[1] ?? 15);
$rows = App\Models\Property::where('added_by', $ownerId)
    ->orderByDesc('id')
    ->limit(10)
    ->get(['id', 'slug_id', 'title', 'status', 'request_status']);

echo json_encode($rows->toArray(), JSON_PRETTY_PRINT), PHP_EOL;
