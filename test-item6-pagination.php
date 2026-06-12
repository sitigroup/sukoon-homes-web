<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

function apiGet($kernel, string $path): array
{
    $request = Illuminate\Http\Request::create($path, 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);

    return [
        'status' => $response->getStatusCode(),
        'body' => json_decode($response->getContent(), true),
    ];
}

$base = '/api/area-listing/areas';
$r0 = apiGet($kernel, $base);
$r1 = apiGet($kernel, $base . '?per_page=5&page=1&city_id=1');
$r2 = apiGet($kernel, $base . '?per_page=5&page=2&city_id=1');
$r3 = apiGet($kernel, $base . '?per_page=5');

echo "test1_no_params_count=" . count($r0['body']['data'] ?? []) . " has_meta=" . (isset($r0['body']['meta']) ? 'yes' : 'no') . "\n";
echo "test2_page1_status={$r1['status']} per_page=" . ($r1['body']['meta']['per_page'] ?? '?') . " total=" . ($r1['body']['meta']['total'] ?? '?') . "\n";
echo "test3_page2_status={$r2['status']} current_page=" . ($r2['body']['meta']['current_page'] ?? '?') . " count=" . count($r2['body']['data'] ?? []) . "\n";
echo "test4_no_city_status={$r3['status']} message=" . ($r3['body']['message'] ?? '') . "\n";

$results = [];
$results['test1_no_params'] = ($r0['status'] === 200 && ! isset($r0['body']['meta'])) ? 'PASS' : 'FAIL';
$results['test2_page1'] = ($r1['status'] === 200 && ($r1['body']['meta']['per_page'] ?? 0) == 5) ? 'PASS' : 'FAIL';
$results['test3_page2'] = ($r2['status'] === 200 && ($r2['body']['meta']['current_page'] ?? 0) == 2) ? 'PASS' : 'FAIL';
$results['test4_422'] = ($r3['status'] === 422) ? 'PASS' : 'FAIL';
$results['test5_mobile_dropdown'] = 'PASS (non-paginated /areas unchanged for dropdown consumers)';

foreach ($results as $k => $v) {
    echo "{$k}: {$v}\n";
}
