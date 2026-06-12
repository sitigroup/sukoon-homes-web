<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Plugins\TrustVerification\Models\TvAuditLog;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvPackage;

$customerId = (int) ($argv[1] ?? 19);
$customer = Customer::findOrFail($customerId);
$customer->tokens()->where('name', 'tv-e2e-qa')->delete();
$token = $customer->createToken('tv-e2e-qa')->plainTextToken;

$package = TvPackage::where('slug', 'tenant-standard-barmer')->first();
$base = 'https://admin-homes.sukoon.group/api/trust-verification';

function tvCall(string $method, string $url, string $token, array $data = [], ?string $filePath = null): array
{
    $ch = curl_init($url);
    $headers = ['Authorization: Bearer '.$token, 'Accept: application/json'];
    if ($filePath) {
        $post = $data;
        $post['file'] = new CURLFile($filePath, mime_content_type($filePath), basename($filePath));
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
        ]);
    } else {
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => array_merge($headers, ['Content-Type: application/json']),
            CURLOPT_POSTFIELDS => $method === 'GET' ? null : json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
        ]);
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $code, 'body' => json_decode($body, true) ?: $body];
}

echo "TOKEN={$token}\n\n";

$r = tvCall('GET', "$base/orders", $token);
echo "LIST orders HTTP {$r['code']}\n";

$tmp = sys_get_temp_dir().'/tv-e2e-api.jpg';
$im = imagecreatetruecolor(8, 8);
imagejpeg($im, $tmp, 85);
imagedestroy($im);

// Create order via API
$create = tvCall('POST', "$base/orders", $token, [
    'package_id' => $package->id,
    'order_type' => 'tenant',
    'city_slug' => 'barmer',
    'subject' => [
        'full_name' => 'API E2E Tenant',
        'phone' => $customer->mobile ?: '9876543210',
        'email' => $customer->email,
        'current_address' => 'Barmer API test',
        'id_type' => 'Aadhaar',
        'id_number' => '9999-8888-7777',
        'consent_given' => true,
    ],
]);
echo "CREATE order HTTP {$create['code']}: ".($create['body']['data']['order_number'] ?? $create['body']['message'] ?? 'err')."\n";
$orderId = $create['body']['data']['id'] ?? null;
$orderNum = $create['body']['data']['order_number'] ?? null;

if ($orderId) {
    $up = tvCall('POST', "$base/orders/{$orderId}/documents", $token, [
        'doc_type' => 'id_front',
    ], $tmp);
    echo "UPLOAD doc HTTP {$up['code']}: ".($up['body']['message'] ?? '')."\n";

    $pi = tvCall('POST', "$base/orders/{$orderId}/payment-intent", $token, [
        'payment_method' => 'cashfree',
        'platform' => 'web',
    ]);
    echo "PAYMENT INTENT HTTP {$pi['code']}: ".($pi['body']['message'] ?? ($pi['body']['error'] ? 'error' : 'ok'))."\n";
    if (! empty($pi['body']['data']['payment_intent']['payment_url'])) {
        echo "Payment URL present: yes\n";
    }

    $audits = TvAuditLog::where('order_id', $orderId)->orderBy('id')->get(['action', 'description']);
    echo "\nAudit log for order {$orderNum}:\n";
    foreach ($audits as $a) {
        echo "  - {$a->action}: {$a->description}\n";
    }
}

@unlink($tmp);
echo "\nORDER_NUM={$orderNum}\nORDER_ID={$orderId}\n";
