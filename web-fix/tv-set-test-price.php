<?php
/**
 * Set Trust Verification Barmer packages to ₹1 for Cashfree test payments.
 * Run on server: php tv-set-test-price.php [1|restore]
 *
 * restore reads tv-package-price-backup.json written on first run.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvPackage;
use Illuminate\Support\Facades\DB;

$mode = $argv[1] ?? '1';
$backupFile = __DIR__.'/tv-package-price-backup.json';
$testPrice = 1;

if ($mode === 'restore') {
    if (! is_file($backupFile)) {
        echo "No backup file at {$backupFile}\n";
        exit(1);
    }
    $backup = json_decode(file_get_contents($backupFile), true);
    foreach ($backup['packages'] as $row) {
        TvPackage::where('id', $row['id'])->update(['price' => $row['price']]);
        echo "Restored package #{$row['id']} {$row['slug']} => ₹{$row['price']}\n";
    }
    if (! empty($backup['orders'])) {
        foreach ($backup['orders'] as $row) {
            TvOrder::where('id', $row['id'])->update([
                'amount' => $row['amount'],
                'payment_transaction_id' => $row['payment_transaction_id'],
            ]);
            echo "Restored order #{$row['id']} {$row['order_number']} amount=₹{$row['amount']}\n";
        }
    }
    echo "Done. Delete backup manually if no longer needed.\n";
    exit(0);
}

$packages = TvPackage::where('city_slug', 'barmer')->orderBy('id')->get();
if ($packages->isEmpty()) {
    echo "No Barmer packages found.\n";
    exit(1);
}

$backup = ['packages' => [], 'orders' => [], 'saved_at' => now()->toIso8601String()];
foreach ($packages as $p) {
    $backup['packages'][] = ['id' => $p->id, 'slug' => $p->slug, 'price' => $p->price];
}
file_put_contents($backupFile, json_encode($backup, JSON_PRETTY_PRINT));

foreach ($packages as $p) {
    $p->update(['price' => $testPrice]);
    echo "Package #{$p->id} {$p->slug} => ₹{$testPrice}\n";
}

$pending = TvOrder::where('payment_status', 'pending')
    ->whereIn('package_id', $packages->pluck('id'))
    ->get();

foreach ($pending as $order) {
    $backup['orders'][] = [
        'id' => $order->id,
        'order_number' => $order->order_number,
        'amount' => $order->amount,
        'payment_transaction_id' => $order->payment_transaction_id,
    ];
    $order->update([
        'amount' => $testPrice,
        'payment_transaction_id' => null,
    ]);
    echo "Pending order {$order->order_number} (#{$order->id}) amount => ₹{$testPrice}, cleared old payment link\n";
}

file_put_contents($backupFile, json_encode($backup, JSON_PRETTY_PRINT));

echo "\nBackup: {$backupFile}\n";
echo "To restore production prices: php tv-set-test-price.php restore\n";
echo "\nCreate a NEW order or use Pay with Cashfree on an updated pending order.\n";
