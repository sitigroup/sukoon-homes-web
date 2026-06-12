<?php

$base = '/www/wwwroot/admin-homes';
chdir($base);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$customerId = 15;
$orders = App\Plugins\TrustVerification\Models\TvOrder::where('customer_id', $customerId)
    ->orderByDesc('id')
    ->limit(10)
    ->get();

foreach ($orders as $order) {
    $txnStatus = 'n/a';
    if ($order->payment_transaction_id) {
        $txn = App\Models\PaymentTransaction::find($order->payment_transaction_id);
        $txnStatus = $txn->payment_status ?? 'missing';
        if ($order->payment_status !== 'paid' && $txn && in_array(strtolower((string) $txn->payment_status), ['success', 'succeed'], true)) {
            App\Plugins\TrustVerification\Services\TrustVerificationPaymentService::syncPaymentStatus($order);
            $order->refresh();
            echo $order->order_number . ": synced to {$order->payment_status}\n";
            continue;
        }
    }
    echo "{$order->order_number}: order={$order->payment_status} txn={$txnStatus}\n";
}
