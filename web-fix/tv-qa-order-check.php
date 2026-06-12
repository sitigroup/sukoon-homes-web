<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$o = App\Plugins\TrustVerification\Models\TvOrder::where('order_number', 'TV-QGX1TNVP')->first();
echo json_encode([
    'order' => $o?->order_number,
    'legal' => $o?->legal_version,
    'consent' => $o?->consent_text,
], JSON_PRETTY_PRINT);
