<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Services\TrustVerificationService;

$num = $argv[1] ?? 'TV-EYMTZLKB';
$o = TvOrder::where('order_number', $num)->with('report')->first();
if (! $o) {
    echo "NOT_FOUND\n";
    exit(1);
}

$f = TrustVerificationService::formatOrder($o, true);
echo json_encode([
    'order_number' => $o->order_number,
    'customer_id' => $o->customer_id,
    'report' => $f['report'] ?? null,
], JSON_PRETTY_PRINT)."\n";
