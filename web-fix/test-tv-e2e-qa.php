<?php
/**
 * Trust Verification E2E QA helper (run on admin-homes server).
 * Usage: php test-tv-e2e-qa.php [--customer-id=N] [--order-id=N]
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Plugins\TrustVerification\Models\TvAuditLog;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvPackage;
use App\Plugins\TrustVerification\Services\TrustVerificationDocumentService;
use App\Plugins\TrustVerification\Services\TrustVerificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

$customerId = null;
$orderId = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--customer-id=')) {
        $customerId = (int) substr($arg, 14);
    }
    if (str_starts_with($arg, '--order-id=')) {
        $orderId = (int) substr($arg, 11);
    }
}

echo "=== TASK 10 TV E2E QA (API/backend) ===\n\n";

$customer = $customerId
    ? Customer::find($customerId)
    : Customer::whereNotNull('email')->whereNotNull('mobile')->orderByDesc('id')->first();

if (! $customer) {
    echo "FAIL: No customer found\n";
    exit(1);
}
echo "Customer: #{$customer->id} {$customer->email}\n";

$package = TvPackage::where('is_active', true)->where('city_slug', 'barmer')->where('type', 'tenant')
    ->where('slug', 'like', '%standard%')->first()
    ?? TvPackage::where('is_active', true)->where('city_slug', 'barmer')->where('type', 'tenant')->first();

if (! $package) {
    echo "FAIL: No tenant package for Barmer\n";
    exit(1);
}
echo "Package: #{$package->id} {$package->name} ({$package->slug})\n";

if ($orderId) {
    $order = TvOrder::with(['documents', 'auditLogs', 'report', 'subject'])->find($orderId);
} else {
    $order = null;
}

if (! $order) {
    echo "\n--- Creating test order via service ---\n";
    try {
        $order = TrustVerificationService::createOrder($customer, $package, [
            'order_type' => 'tenant',
            'city_slug' => 'barmer',
            'subject' => [
                'full_name' => 'E2E QA Test Tenant',
                'phone' => $customer->mobile ?: '9876543210',
                'email' => $customer->email,
                'current_address' => 'Barmer test address',
                'id_type' => 'Aadhaar',
                'id_number' => '1234-5678-9012',
                'consent_given' => true,
            ],
            'notes' => 'TASK 10 automated E2E QA '.date('c'),
        ], null);
        echo "Created order: {$order->order_number} (id {$order->id})\n";
        echo "Consent: ".($order->consent_given ? 'yes' : 'no')."\n";
    } catch (Throwable $e) {
        echo "FAIL create order: {$e->getMessage()}\n";
        exit(1);
    }
}

$order->refresh()->load(['documents', 'auditLogs', 'report', 'subject']);

echo "\n--- Order snapshot ---\n";
echo "Number: {$order->order_number}\n";
echo "Status: {$order->status} | Payment: {$order->payment_status}\n";
echo "Amount: {$order->amount}\n";

$audits = TvAuditLog::where('order_id', $order->id)->orderBy('id')->pluck('action')->all();
echo "Audit actions: ".implode(', ', $audits ?: ['none'])."\n";

// Upload tiny valid JPEG if no documents
if ($order->documents->whereNull('deleted_at')->isEmpty()) {
    echo "\n--- Upload test JPEG ---\n";
    $tmp = sys_get_temp_dir().'/tv-e2e-'.uniqid().'.jpg';
    $img = imagecreatetruecolor(10, 10);
    imagejpeg($img, $tmp, 90);
    imagedestroy($img);
    $uploaded = new UploadedFile($tmp, 'e2e-test.jpg', 'image/jpeg', null, true);
    try {
        $doc = TrustVerificationDocumentService::store($order, 'id_front', $uploaded, $customer);
        echo "Document uploaded: #{$doc->id} {$doc->mime_type} {$doc->size_bytes} bytes\n";
        @unlink($tmp);
    } catch (Throwable $e) {
        echo "FAIL upload: {$e->getMessage()}\n";
        @unlink($tmp);
    }
}

$order->refresh()->load('documents');
echo "Documents: ".$order->documents->count()."\n";

// Rate limit smoke: middleware registered?
$registrar = class_exists(\App\Plugins\TrustVerification\Services\TrustVerificationRateLimiterRegistrar::class);
echo "\nRate limit registrar: ".($registrar ? 'OK' : 'MISSING')."\n";

// Recent laravel errors
$log = storage_path('logs/laravel.log');
if (is_file($log)) {
    $tail = shell_exec('tail -n 200 '.escapeshellarg($log).' 2>/dev/null');
    $fatals = substr_count(strtolower($tail ?? ''), 'trustverification') > 0
        ? preg_match_all('/\[.*ERROR.*\].*TrustVerification/i', $tail ?? '', $m)
        : 0;
    echo "Recent log TrustVerification ERROR lines (tail 200): ".(int) $fatals."\n";
}

echo "\n=== Use for browser QA ===\n";
echo "Order ID: {$order->id}\n";
echo "Order number: {$order->order_number}\n";
echo "Customer ID: {$customer->id}\n";
echo "Admin: https://admin-homes.sukoon.group/trust-verification (search {$order->order_number})\n";
echo "My orders: https://homes.sukoon.group/my-verification-orders/?lang=en\n";
