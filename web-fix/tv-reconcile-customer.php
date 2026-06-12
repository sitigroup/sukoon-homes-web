<?php

// Usage on server: cd /www/wwwroot/admin-homes && php /path/to/tv-reconcile-customer.php [customer_id]

$customerId = (int) ($argv[1] ?? 15);

$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PaymentTransaction;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Services\TrustVerificationPaymentService;

$orders = TvOrder::where('customer_id', $customerId)->orderByDesc('id')->limit(10)->get();

foreach ($orders as $order) {
    $txnStatus = 'n/a';
    if ($order->payment_transaction_id) {
        $txn = PaymentTransaction::find($order->payment_transaction_id);
        $txnStatus = $txn->payment_status ?? 'missing';
        if ($order->payment_status !== 'paid' && $txn && in_array(strtolower((string) $txn->payment_status), ['success', 'succeed'], true)) {
            TrustVerificationPaymentService::syncPaymentStatus($order);
            $order->refresh();
            echo "{$order->order_number}: synced to {$order->payment_status}\n";
            continue;
        }
    }
    echo "{$order->order_number}: order={$order->payment_status} txn={$txnStatus}\n";
}
