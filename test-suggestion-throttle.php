<?php

/**
 * Run on server: php test-suggestion-throttle.php
 * Usage: php test-suggestion-throttle.php [base_url] [token]
 */

$base = rtrim($argv[1] ?? 'https://admin-homes.sukoon.group/api', '/');
$token = $argv[2] ?? '';

if ($token === '') {
    require __DIR__ . '/../admin-homes/vendor/autoload.php';
    $app = require __DIR__ . '/../admin-homes/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    $customer = App\Models\Customer::query()->whereNotNull('email')->first();
    if (! $customer) {
        fwrite(STDERR, "No customer found\n");
        exit(1);
    }
    $customer->tokens()->where('name', 'throttle-test')->delete();
    $token = $customer->createToken('throttle-test')->plainTextToken;
    echo "TOKEN={$token}\n";
    echo "CUSTOMER_ID={$customer->id}\n";
}

function requestSuggest(string $base, string $token, string $path, string $name, int $index): array
{
    $url = "{$base}/location/{$path}";
    $payload = $path === 'suggest-area'
        ? ['name' => $name, 'city' => 'Barmer', 'state' => 'Rajasthan', 'country' => 'India']
        : ['name' => $name, 'area_id' => 1];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30,
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $headerSize = strpos($raw, "\r\n\r\n");
    $headers = $headerSize !== false ? substr($raw, 0, $headerSize) : '';
    $body = $headerSize !== false ? substr($raw, $headerSize + 4) : $raw;
    $retryAfter = null;
    if (preg_match('/Retry-After:\s*(\S+)/i', $headers, $m)) {
        $retryAfter = $m[1];
    }

    return [
        'index' => $index,
        'path' => $path,
        'name' => $name,
        'status' => $status,
        'retry_after' => $retryAfter,
        'body' => $body,
    ];
}

function requestGet(string $base, string $path): array
{
    $ch = curl_init("{$base}/{$path}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_TIMEOUT => 15,
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['path' => $path, 'status' => $status, 'body' => substr((string) $body, 0, 120)];
}

echo "=== Suggest-area throttle test (5 per hour) ===\n";
for ($i = 1; $i <= 6; $i++) {
    $r = requestSuggest($base, $token, 'suggest-area', 'Throttle Test Area ' . $i, $i);
    echo "#{$r['index']} status={$r['status']} retry_after=" . ($r['retry_after'] ?? '-') . "\n";
}

echo "\n=== Other Area Wise GET endpoints ===\n";
foreach (['location/states', 'location/areas?city=Barmer&state=Rajasthan', 'area-listing/states'] as $p) {
    $r = requestGet($base, $p);
    echo "{$r['path']} status={$r['status']}\n";
}
