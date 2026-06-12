<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

function api($kernel, string $method, string $path, array $data = []): array
{
    $uri = str_starts_with($path, '/') ? $path : '/' . $path;
    $request = Illuminate\Http\Request::create($uri, $method, $data, [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    $body = json_decode($response->getContent(), true);

    return [
        'status' => $response->getStatusCode(),
        'headers' => $response->headers->all(),
        'body' => $body,
        'raw' => $response->getContent(),
    ];
}

$privateKeys = ['address', 'latitude', 'longitude', 'manual_address', 'full_address', 'client_address'];

function hasPrivateFields($data, array $keys): array
{
    $found = [];
    $json = json_encode($data);
    foreach ($keys as $key) {
        if (preg_match('/"' . preg_quote($key, '/') . '"/', $json)) {
            $found[] = $key;
        }
    }
    return $found;
}

$results = [];

// Section 8 - public APIs
$endpoints = [
    'states' => '/api/area-listing/states',
    'cities' => '/api/area-listing/cities?state_id=1',
    'areas_al' => '/api/area-listing/areas?city_id=1',
    'areas_loc' => '/api/location/areas?city_id=1',
    'sub_areas' => '/api/area-listing/sub-areas?area_id=1',
];
foreach ($endpoints as $name => $path) {
    $r = api($kernel, 'GET', $path);
    $leaked = hasPrivateFields($r['body'], $privateKeys);
    $results["s8_{$name}"] = ($r['status'] === 200 && empty($leaked)) ? 'PASS' : "FAIL status={$r['status']} leaked=" . implode(',', $leaked);
}

// Section 9 - pagination
$p1 = api($kernel, 'GET', '/api/area-listing/areas?per_page=5&page=1&city_id=1');
$p2 = api($kernel, 'GET', '/api/area-listing/areas?per_page=5');
$results['s9_page1'] = ($p1['status'] === 200 && isset($p1['body']['meta']['per_page'])) ? 'PASS' : 'FAIL';
$results['s9_page422'] = ($p2['status'] === 422) ? 'PASS' : 'FAIL status=' . $p2['status'];

// Section 7 - rate limit (need auth token - try without first)
echo "===SECTION_8_9===\n";
foreach ($results as $k => $v) {
    echo "{$k}: {$v}\n";
}
echo "s9_meta=" . json_encode($p1['body']['meta'] ?? null) . "\n";
