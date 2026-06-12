<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::query()->first();
if (! $user) {
    echo "7_no_user: FAIL\n";
    exit(1);
}

Laravel\Sanctum\Sanctum::actingAs($user);

$codes = [];
$retryAfter = null;
for ($i = 1; $i <= 6; $i++) {
    $request = Illuminate\Http\Request::create('/api/location/suggest-area', 'POST', [
        'name' => 'Rate Limit Verify ' . $i . ' ' . microtime(true),
        'city' => 'Barmer',
        'state' => 'Rajasthan',
        'country' => 'India',
    ]);
    $request->headers->set('Accept', 'application/json');

    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    $codes[] = $response->getStatusCode();
    if ($i === 6) {
        $retryAfter = $response->headers->get('Retry-After');
        if ($response->getStatusCode() >= 400) {
            echo "6th_body=" . substr($response->getContent(), 0, 200) . "\n";
        }
    }
}

echo "codes=" . implode(',', $codes) . "\n";
echo "7_first5: " . (array_slice($codes, 0, 5) === [200, 200, 200, 200, 200] ? 'PASS' : 'FAIL') . "\n";
echo "7_sixth_429: " . ($codes[5] === 429 ? 'PASS' : 'FAIL') . "\n";
echo "7_retry_after: " . ($retryAfter ? "PASS value={$retryAfter}" : 'FAIL') . "\n";
