<?php
/**
 * TASK 10 full backend E2E (run on admin-homes).
 * Usage: php test-tv-e2e-full.php [order_id]
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Plugins\TrustVerification\Models\TvAuditLog;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvOrderDocument;
use App\Plugins\TrustVerification\Services\TrustVerificationAuditLogService;
use App\Plugins\TrustVerification\Services\TrustVerificationAutomationService;
use App\Plugins\TrustVerification\Services\TrustVerificationDocumentService;
use App\Plugins\TrustVerification\Services\TrustVerificationPiiRetentionService;
use App\Plugins\TrustVerification\Services\TrustVerificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

$orderId = (int) ($argv[1] ?? 4);
$order = TvOrder::with(['documents', 'report', 'auditLogs'])->findOrFail($orderId);
$customer = Customer::findOrFail($order->customer_id);

echo "Order {$order->order_number} (#{$order->id}) payment={$order->payment_status}\n\n";

// 1) Upload via API
$customer->tokens()->where('name', 'tv-e2e-full')->delete();
$token = $customer->createToken('tv-e2e-full')->plainTextToken;
$tmp = sys_get_temp_dir().'/tv-e2e-full.jpg';
$im = imagecreatetruecolor(12, 12);
imagejpeg($im, $tmp, 90);
imagedestroy($im);
$ch = curl_init("https://admin-homes.sukoon.group/api/trust-verification/orders/{$orderId}/documents");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$token, 'Accept: application/json'],
    CURLOPT_POSTFIELDS => [
        'doc_type' => 'id_front',
        'file' => new CURLFile($tmp, 'image/jpeg', 'e2e.jpg'),
    ],
]);
$uploadBody = curl_exec($ch);
$uploadCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
@unlink($tmp);
echo "UPLOAD HTTP {$uploadCode}: {$uploadBody}\n\n";

$order->refresh()->load('documents');
$doc = $order->documents->whereNull('deleted_at')->first();
echo "Documents active: ".$order->documents->whereNull('deleted_at')->count()."\n";

// 2) Mark paid (admin simulation)
if ($order->payment_status !== 'paid') {
    $prev = $order->payment_status;
    $order->update(['payment_status' => 'paid', 'status' => 'in_progress']);
    TrustVerificationAuditLogService::log([
        'order_id' => $order->id,
        'action' => TrustVerificationAuditLogService::ACTION_ADMIN_CHANGED_PAYMENT_STATUS,
        'description' => "Payment status changed from {$prev} to paid (E2E QA)",
        'metadata' => ['from' => $prev, 'to' => 'paid'],
    ]);
    echo "Marked paid (was {$prev})\n";
}

// 3) Automation dispatch smoke
try {
    $run = TrustVerificationAutomationService::runForOrder($order->fresh(), 'e2e_qa');
    echo "Automation run: #{$run->id} status {$run->status}\n";
} catch (Throwable $e) {
    echo "Automation run: ".$e->getMessage()."\n";
}

// 4) Upload dummy PDF report
$pdf = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF";
$pdfTmp = sys_get_temp_dir().'/tv-report.pdf';
file_put_contents($pdfTmp, $pdf);
$reportFile = new UploadedFile($pdfTmp, 'e2e-report.pdf', 'application/pdf', null, true);
try {
    $report = TrustVerificationService::storeReportFile(
        $order->fresh(),
        $reportFile,
        null,
        'green',
        'E2E QA dummy report'
    );
    TrustVerificationAuditLogService::log([
        'order_id' => $order->id,
        'action' => TrustVerificationAuditLogService::ACTION_ADMIN_UPLOADED_REPORT,
        'description' => 'E2E QA uploaded report PDF',
        'metadata' => ['report_id' => $report->id],
    ]);
    echo "Report uploaded: #{$report->id}\n";
} catch (Throwable $e) {
    echo "Report upload FAIL: {$e->getMessage()}\n";
}
@unlink($pdfTmp);

// 5) Customer report download smoke
$ch = curl_init("https://admin-homes.sukoon.group/api/trust-verification/orders/{$orderId}/report/download");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$token, 'Accept: application/json'],
    CURLOPT_HEADER => true,
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "Report download HTTP {$code}\n";

// 6) PII delete document
if ($doc) {
    $path = $doc->file_path;
    $purge = TrustVerificationPiiRetentionService::purgeOrderDocuments(
        $order,
        TrustVerificationPiiRetentionService::DELETED_BY_ADMIN,
        null,
        'E2E QA PII delete',
        false
    );
    echo "PII purge count: {$purge['count']}\n";
    $exists = $path && Storage::disk(TrustVerificationDocumentService::DISK)->exists($path);
    echo "Doc deleted; file on disk: ".($exists ? 'YES (fail)' : 'no (ok)')."\n";
    $ch = curl_init("https://admin-homes.sukoon.group/api/trust-verification/orders/{$orderId}/documents/{$doc->id}/download");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_NOBODY => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$token], CURLOPT_HEADER => true]);
    curl_exec($ch);
    $dlCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "Doc download after delete HTTP {$dlCode}\n";
}

// 7) Rate limit smoke — one more payment-intent should not 429
$ch = curl_init("https://admin-homes.sukoon.group/api/trust-verification/orders/{$orderId}/payment-intent");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$token, 'Accept: application/json', 'Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode(['payment_method' => 'cashfree', 'platform' => 'web']),
]);
$pi = curl_exec($ch);
$piCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "Payment-intent smoke HTTP {$piCode}\n";

echo "\n=== Audits ===\n";
foreach (TvAuditLog::where('order_id', $orderId)->orderBy('id')->get(['action', 'description']) as $a) {
    echo "  {$a->action}: {$a->description}\n";
}

echo "\nORDER={$order->order_number}\n";
