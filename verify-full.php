<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Plugins\AreaListing\Http\Controllers\Admin\AreaListingAdminController;
use App\Plugins\AreaListing\Services\AreaListingPropertyLocationRepairService;
use App\Plugins\AreaListing\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

function httpApi($kernel, string $method, string $path, array $server = []): array
{
    $request = Illuminate\Http\Request::create($path, $method, [], [], [], array_merge([
        'HTTP_ACCEPT' => 'application/json',
    ], $server));
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    return [
        'status' => $response->getStatusCode(),
        'headers' => $response->headers,
        'body' => json_decode($response->getContent(), true),
        'raw' => $response->getContent(),
    ];
}

$out = [];

// 8 + 9
$paths = [
    'states' => '/api/area-listing/states',
    'cities' => '/api/area-listing/cities?state_id=1',
    'areas1' => '/api/area-listing/areas?city_id=1',
    'areas2' => '/api/location/areas?city_id=1',
    'sub' => '/api/area-listing/sub-areas?area_id=1',
];
$private = ['manual_address', 'full_address', 'client_address'];
foreach ($paths as $k => $p) {
    $r = httpApi($kernel, 'GET', $p);
    $leak = false;
    foreach ($private as $key) {
        if (str_contains($r['raw'], '"' . $key . '"')) {
            $leak = true;
        }
    }
    $out["8_{$k}"] = ($r['status'] === 200 && ! $leak) ? 'PASS' : "FAIL {$r['status']} leak=" . ($leak ? 'yes' : 'no');
}
$p1 = httpApi($kernel, 'GET', '/api/area-listing/areas?per_page=5&page=1&city_id=1');
$p2 = httpApi($kernel, 'GET', '/api/area-listing/areas?per_page=5');
$out['9_paginated'] = ($p1['status'] === 200 && isset($p1['body']['meta'])) ? 'PASS' : 'FAIL';
$out['9_422'] = ($p2['status'] === 422) ? 'PASS' : 'FAIL';

// 7 rate limit
$user = User::query()->whereNotNull('email')->first();
$codes = [];
for ($i = 1; $i <= 6; $i++) {
    $req = Illuminate\Http\Request::create('/api/location/suggest-area', 'POST', [
        'name' => 'Rate Test ' . $i . ' ' . time(),
        'city' => 'Barmer',
        'state' => 'Rajasthan',
        'country' => 'India',
    ], [], [], ['HTTP_ACCEPT' => 'application/json']);
    if ($user) {
        $req->setUserResolver(fn () => $user);
    }
    $resp = $kernel->handle($req);
    $kernel->terminate($req, $resp);
    $codes[] = $resp->getStatusCode();
    $retry = $i === 6 ? $resp->headers->get('Retry-After') : null;
}
$out['7_first5'] = (array_slice($codes, 0, 5) === array_fill(0, 5, 200)) ? 'PASS' : 'FAIL ' . implode(',', $codes);
$out['7_sixth'] = ($codes[5] === 429) ? 'PASS' : 'FAIL code=' . $codes[5];
$out['7_retry'] = isset($retry) && $retry !== null && $retry !== '' ? 'PASS' : 'NOTE retry=' . ($retry ?? 'none');

// 6 tools - repair + drift via controller JSON simulation
$admin = app(AreaListingAdminController::class);
$repair = json_decode($admin->repairLocationsDryRun()->getContent(), true);
$out['6_repair'] = ((int) ($repair['missing'] ?? -1) === 0) ? 'PASS' : 'FAIL missing=' . ($repair['missing'] ?? '?');

$drift = json_decode($admin->checkCityDrift()->getContent(), true);
$out['6_drift'] = ((int) ($drift['total_drift'] ?? -1) === 0) ? 'PASS' : 'FAIL drift=' . ($drift['total_drift'] ?? '?');

// 5 archived areasData
$arch = json_decode($admin->areasData(Request::create('/', 'GET', ['archived' => 1, 'city' => 'Barmer', 'state' => 'Rajasthan']))->getContent(), true);
$hasFields = empty($arch['rows']) || (isset($arch['rows'][0]['days_archived']) && isset($arch['rows'][0]['archived_label']));
$out['5_archived_api'] = $hasFields ? 'PASS' : 'FAIL';

// 2 similarity via storeArea
$cityId = 1;
$req1 = Request::create('/area-listing/areas', 'POST', [
    'name' => 'Verify Similar Alpha',
    'city_id' => $cityId,
    'city' => 'Barmer',
    'state' => 'Rajasthan',
    'country' => 'India',
]);
$req1->headers->set('Accept', 'application/json');
$r1 = $admin->storeArea($req1);
$req2 = Request::create('/area-listing/areas', 'POST', [
    'name' => 'Verify Similar Alpa',
    'city_id' => $cityId,
    'city' => 'Barmer',
    'state' => 'Rajasthan',
    'country' => 'India',
]);
$req2->headers->set('Accept', 'application/json');
$r2 = $admin->storeArea($req2);
$out['10_similar_409'] = ($r2->getStatusCode() === 409) ? 'PASS' : 'FAIL status=' . $r2->getStatusCode();
Area::query()->where('normalized_name', 'like', 'verify similar%')->forceDelete();

// counts
$out['db_properties'] = 'INFO properties=' . DB::table('propertys')->count();
$out['db_loc_rows'] = 'INFO property_locations=' . DB::table('area_listing_property_locations')->count();

foreach ($out as $k => $v) {
    echo "{$k}: {$v}\n";
}
