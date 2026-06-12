<?php

// Run: cd /www/wwwroot/admin-homes && php test-suggestion-throttle-server.php

$root = getenv('APP_ROOT') ?: '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$base = rtrim(getenv('API_BASE') ?: 'https://admin-homes.sukoon.group/api', '/');

$customer = App\Models\Customer::query()->whereNotNull('email')->first();
if (! $customer) {
    fwrite(STDERR, "No customer found\n");
    exit(1);
}
$customer->tokens()->where('name', 'throttle-test')->delete();
$token = $customer->createToken('throttle-test')->plainTextToken;
echo "TOKEN_OK customer_id={$customer->id}\n";

function requestSuggest(string $base, string $token, string $path, string $name, int $index): array
{
    $url = "{$base}/location/{$path}";
    $payload = $path === 'suggest-area'
        ? ['name' => $name, 'city' => 'Barmer', 'state' => 'Rajasthan', 'country' => 'India']
        : ['name' => $name, 'area_id' => (int) (App\Plugins\AreaListing\Models\Area::query()->value('id') ?: 1)];

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
    $raw = (string) curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $retryAfter = null;
    if (preg_match('/Retry-After:\s*(\S+)/i', $raw, $m)) {
        $retryAfter = $m[1];
    }

    return compact('index', 'path', 'name', 'status', 'retryAfter');
}

function requestGet(string $base, string $path, ?string $token = null): array
{
    $headers = ['Accept: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    $ch = curl_init("{$base}/{$path}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
    ]);
    curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['path' => $path, 'status' => $status];
}

echo "=== TEST 1-2: suggest-area x6 (expect 200,200,200,200,200,429) ===\n";
$results = [];
for ($i = 1; $i <= 6; $i++) {
    $r = requestSuggest($base, $token, 'suggest-area', 'Throttle Test Area ' . uniqid(), $i);
    $results[] = $r;
    echo "#{$r['index']} status={$r['status']} retry_after=" . ($r['retryAfter'] ?? '-') . "\n";
}

echo "=== TEST 3: other GET endpoints ===\n";
foreach (['location/states', 'location/cities?state=Rajasthan', 'area-listing/states'] as $p) {
    $r = requestGet($base, $p);
    echo "{$r['path']} status={$r['status']}\n";
}

echo "=== TEST 4: authenticated POST area-listing/states (not throttled) ===\n";
$ch = curl_init("{$base}/area-listing/states");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token,
    ],
    CURLOPT_POSTFIELDS => json_encode(['name' => 'Test State ' . uniqid(), 'country' => 'India']),
]);
curl_exec($ch);
echo 'area-listing/states POST status=' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
curl_close($ch);

echo "=== TEST 5: admin web route (no API) ===\n";
$ch = curl_init('https://admin-homes.sukoon.group/area-listing');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 15]);
curl_exec($ch);
echo 'GET /area-listing status=' . curl_getinfo($ch, CURLINFO_HTTP_CODE) . " (expect 302 login)\n";
curl_close($ch);

$pass = true;
for ($i = 0; $i < 5; $i++) {
    if (($results[$i]['status'] ?? 0) !== 200) {
        $pass = false;
    }
}
if (($results[5]['status'] ?? 0) !== 429) {
    $pass = false;
}
if (empty($results[5]['retryAfter'])) {
    $pass = false;
}
echo $pass ? "OVERALL_PASS\n" : "OVERALL_FAIL\n";
