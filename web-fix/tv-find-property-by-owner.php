<?php
$base = '/www/wwwroot/admin-homes';
chdir($base);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ownerId = (int) ($argv[1] ?? 15);
$p = App\Models\Property::where('added_by', $ownerId)
    ->where('status', 1)
    ->orderByDesc('id')
    ->first(['id', 'slug_id', 'title', 'added_by']);

echo json_encode($p ? $p->toArray() : null, JSON_PRETTY_PRINT), PHP_EOL;
