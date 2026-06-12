<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$token = $argv[1] ?? '';
$uri = '/area-listing/repair-locations/dry-run?token=' . urlencode($token);

$request = Illuminate\Http\Request::create($uri, 'POST', [], [], [], [
    'HTTP_ACCEPT' => 'application/json',
]);

$response = $app->handle($request);
echo 'internal query-only => ' . $response->getStatusCode() . PHP_EOL;
echo substr($response->getContent(), 0, 100) . PHP_EOL;
