<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Plugins\TrustVerification\Models\TvPackage;
use App\Plugins\TrustVerification\Services\TrustVerificationService;
use App\Plugins\TrustVerification\Models\TvOrder;
use Illuminate\Support\Facades\Hash;

$email = $argv[1] ?? 'hemssarda@gmail.com';
$password = $argv[2] ?? 'hems7827';

$customer = Customer::where('email', $email)->where('logintype', 1)->orderByDesc('id')->first();
if (! $customer) {
    echo json_encode(['error' => 'customer not found']) . "\n";
    exit(1);
}

if (! Hash::check($password, $customer->password)) {
    echo json_encode(['error' => 'bad password']) . "\n";
    exit(1);
}

$token = $customer->createToken('tv-qa-12b')->plainTextToken;
$package = TvPackage::where('is_active', true)->where('city_slug', 'barmer')->where('type', 'tenant')->first();
if (! $package) {
    echo json_encode(['error' => 'no package']) . "\n";
    exit(1);
}

$ch = curl_init('https://admin-homes.sukoon.group/api/trust-verification/orders');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'package_id' => $package->id,
        'city_slug' => 'barmer',
        'notes' => 'TASK12B consent CMS QA',
        'subject' => [
            'full_name' => 'TASK12B QA Tenant',
            'phone' => '9876512345',
            'email' => 'task12b-qa@example.com',
            'consent_given' => true,
        ],
    ]),
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$json = json_decode($body, true);
$orderId = $json['data']['id'] ?? null;
$order = $orderId ? TvOrder::find($orderId) : null;

echo json_encode([
    'http' => $code,
    'order_number' => $order?->order_number,
    'consent_text' => $order?->consent_text,
    'legal_version' => $order?->legal_version,
    'api_error' => $json['error'] ?? null,
    'message' => $json['message'] ?? null,
], JSON_PRETTY_PRINT) . "\n";
