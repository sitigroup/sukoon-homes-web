<?php
/**
 * QA Task 13C timestamps. Usage: cd /www/wwwroot/admin-homes && php web-fix/tv-qa-timestamps.php TV-EYMTZLKB
 */
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Services\TrustVerificationOrderTimestampsService as TvTs;
use App\Plugins\TrustVerification\Services\TrustVerificationService;
use Carbon\Carbon;

$orderNumber = $argv[1] ?? 'TV-EYMTZLKB';
$order = TvOrder::where('order_number', $orderNumber)->first();
if (! $order) {
    fwrite(STDERR, "Order not found: {$orderNumber}\n");
    exit(1);
}

$ts = TvTs::resolve($order);
$display = [];
foreach ($ts as $key => $iso) {
    $display[$key] = $iso
        ? TvTs::formatDisplay(Carbon::parse($iso))
        : 'Not recorded';
}

echo json_encode([
    'order_number' => $order->order_number,
    'timestamps_iso' => $ts,
    'timestamps_ist' => $display,
    'api_snippet' => TrustVerificationService::formatOrder($order, true, $ts)['timestamps'] ?? null,
], JSON_PRETTY_PRINT)."\n";
