<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$r = app(\App\Plugins\Whatsapp\Services\MetaGraphClient::class)->fetchTemplatesResult();
echo json_encode([
    'ok' => $r['ok'] ?? false,
    'count' => count($r['items'] ?? []),
    'message' => $r['message'] ?? null,
], JSON_PRETTY_PRINT) . PHP_EOL;
