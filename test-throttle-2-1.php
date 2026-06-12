<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$customer = App\Models\Customer::find(1);
$customer->tokens()->where('name', 'throttle-test-2-1')->delete();
$token = $customer->createToken('throttle-test-2-1')->plainTextToken;
$base = 'https://admin-homes.sukoon.group/api';

function hit($base, $token, $i): array
{
    $ch = curl_init("{$base}/location/suggest-sub-area");
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
            'name' => 'Sub Throttle ' . $i . ' ' . uniqid(),
            'area_id' => (int) (App\Plugins\AreaListing\Models\Area::query()->value('id') ?: 1),
        ]),
    ]);
    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    preg_match('/Retry-After:\s*(\S+)/i', $raw, $m);
    return ['i' => $i, 'status' => $status, 'retry' => $m[1] ?? '-'];
}

Illuminate\Support\Facades\Artisan::call('cache:clear');
echo "cache cleared\n";
for ($i = 1; $i <= 3; $i++) {
    $r = hit($base, $token, $i);
    echo "before wait #{$r['i']} status={$r['status']} retry={$r['retry']}\n";
}
echo "sleep 61s...\n";
sleep(61);
$r = hit($base, $token, 4);
echo "after wait #{$r['i']} status={$r['status']} retry={$r['retry']}\n";
