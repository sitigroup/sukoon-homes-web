<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

function httpJson(string $method, string $uri, array $headers = [], string $body = ''): array
{
    $request = Request::create($uri, $method, [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/json',
    ] + array_reduce(array_keys($headers), function ($carry, $key) use ($headers) {
        $carry['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $headers[$key];

        return $carry;
    }, []));

    if ($body !== '') {
        $request->initialize(
            $request->query->all(),
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $body
        );
    }

    $response = app()->handle($request);

    return [
        'status' => $response->getStatusCode(),
        'body' => json_decode($response->getContent(), true),
    ];
}

echo "9.1 suggest-area no auth:\n";
$r = httpJson('POST', '/api/location/suggest-area', [], '{}');
print_r($r);

echo "\n6.1 repair dry-run no auth:\n";
$r = httpJson('POST', '/area-listing/repair-locations/dry-run', [], '{}');
print_r($r);

echo "\nAPI repair dry-run no auth:\n";
$r = httpJson('POST', '/api/area-listing/repair-locations/dry-run', [], '{}');
print_r($r);
