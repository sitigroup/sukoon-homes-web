#!/usr/bin/env bash
cd /www/wwwroot/admin-homes
php artisan tinker --execute='
$cid = (int) ($argv[0] ?? 15);
$orders = App\Plugins\TrustVerification\Models\TvOrder::where("customer_id", $cid)->orderByDesc("id")->limit(10)->get();
foreach ($orders as $order) {
  $txnStatus = "n/a";
  if ($order->payment_transaction_id) {
    $txn = App\Models\PaymentTransaction::find($order->payment_transaction_id);
    $txnStatus = $txn->payment_status ?? "missing";
    if ($order->payment_status !== "paid" && $txn && in_array(strtolower((string) $txn->payment_status), ["success", "succeed"], true)) {
      App\Plugins\TrustVerification\Services\TrustVerificationPaymentService::syncPaymentStatus($order);
      $order->refresh();
      echo $order->order_number . ": synced to " . $order->payment_status . PHP_EOL;
      continue;
    }
  }
  echo $order->order_number . ": order=" . $order->payment_status . " txn=" . $txnStatus . PHP_EOL;
}
' -- "$1"
