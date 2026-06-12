<?php

$base = '/www/wwwroot/admin-homes';
chdir($base);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = App\Plugins\TrustVerification\Models\TvOrder::where('order_number', 'TV-FFUMGSBT')->first();
if (! $order) {
    echo "order not found\n";
    exit(1);
}
$txn = App\Models\PaymentTransaction::find($order->payment_transaction_id);
echo json_encode([
    'order' => $order->order_number,
    'payment_status' => $order->payment_status,
    'txn_id' => $txn?->id,
    'txn_status' => $txn?->payment_status,
    'txn_order_id' => $txn?->order_id,
    'amount' => $txn?->amount,
], JSON_PRETTY_PRINT) . "\n";
