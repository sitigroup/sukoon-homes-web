<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::query()->first();
Laravel\Sanctum\Sanctum::actingAs($user);

// Clear rate limiter for this user/ip
$limiter = app(Illuminate\Cache\RateLimiter::class);
$key = 'location|suggest-area|' . $user->id;
try {
    $limiter->clear('location|suggest-area|' . $user->id);
} catch (Throwable $e) {
}
Illuminate\Support\Facades\RateLimiter::clear('location|suggest-area|' . $user->id);

$codes = [];
for ($i = 1; $i <= 6; $i++) {
    $request = Illuminate\Http\Request::create('/api/location/suggest-area', 'POST', [
        'name' => 'Fresh Rate ' . uniqid(),
        'city' => 'Barmer',
        'state' => 'Rajasthan',
        'country' => 'India',
    ]);
    $request->headers->set('Accept', 'application/json');
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    $codes[] = $response->getStatusCode();
    if ($i === 1) {
        echo "first_body=" . substr($response->getContent(), 0, 300) . "\n";
    }
    if ($i === 6) {
        echo "sixth_retry=" . $response->headers->get('Retry-After') . "\n";
    }
}
echo "codes=" . implode(',', $codes) . "\n";
