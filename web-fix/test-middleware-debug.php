<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$token = $argv[1] ?? '';
$request = Illuminate\Http\Request::create(
    '/area-listing/repair-locations/dry-run?token=' . urlencode($token),
    'POST'
);

echo 'query token=' . ($request->query('token') ?: 'none') . PHP_EOL;

$middleware = new App\Http\Middleware\SanctumTokenFromQuery();
$middleware->handle($request, static fn ($req) => $req);

echo 'bearer after middleware=' . ($request->bearerToken() ?: 'none') . PHP_EOL;
