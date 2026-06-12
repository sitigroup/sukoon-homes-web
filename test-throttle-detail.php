<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$customer = App\Models\Customer::find(1);
$customer->tokens()->where('name', 'throttle-test')->delete();
$token = $customer->createToken('throttle-test')->plainTextToken;
$base = 'https://admin-homes.sukoon.group/api';

for ($i = 1; $i <= 7; $i++) {
    $ch = curl_init("{$base}/location/suggest-area");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'name' => 'Detail Test ' . $i . ' ' . time(),
            'city' => 'Barmer',
            'state' => 'Rajasthan',
            'country' => 'India',
        ]),
    ]);
    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    preg_match('/Retry-After:\s*(\S+)/i', $raw, $m);
    $bodyStart = strpos($raw, "\r\n\r\n");
    $body = $bodyStart ? substr($raw, $bodyStart + 4, 300) : '';
    echo "#$i HTTP $status Retry-After=" . ($m[1] ?? '-') . " body=" . trim($body) . "\n";
}
