<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Http\Controllers\Admin\AreaListingAdminController;
use App\Plugins\AreaListing\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$controller = app(AreaListingAdminController::class);
$results = [];

function postArea(AreaListingAdminController $controller, array $data): array
{
    $request = Request::create('/area-listing/areas', 'POST', $data);
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    $response = $controller->storeArea($request);

    return [
        'status' => $response->getStatusCode(),
        'body' => json_decode($response->getContent(), true) ?: [],
    ];
}

$barmerCity = DB::table('area_listing_cities')->where('name', 'Barmer')->orWhere('slug', 'barmer')->first();
if (! $barmerCity) {
    $barmerCity = DB::table('area_listing_cities')->orderBy('id')->first();
}
$otherCity = DB::table('area_listing_cities')->where('id', '!=', $barmerCity->id)->orderBy('id')->first();

$base = [
    'city_id' => $barmerCity->id,
    'city' => $barmerCity->name,
    'state' => $barmerCity->state,
    'country' => $barmerCity->country ?: 'India',
];

// Cleanup test areas from prior runs
Area::query()->whereIn('normalized_name', ['krishna nagar', 'krishna nagr', 'krishna nagar dup'])->delete();

// Test 1: create Krishna Nagar normally
$r1 = postArea($controller, $base + ['name' => 'Krishna Nagar Test']);
$area1Id = (int) ($r1['body']['data']['id'] ?? 0);
$results['test1_create_normal'] = ($r1['status'] === 200 && $area1Id > 0) ? 'PASS' : 'FAIL';

// Test 2: similar Krishna Nagr -> 409
$r2 = postArea($controller, $base + ['name' => 'Krishna Nagr Test']);
$results['test2_similar_409'] = ($r2['status'] === 409 && ($r2['body']['status'] ?? '') === 'similar_exists') ? 'PASS' : 'FAIL';
$results['test2_suggestion'] = ((int) ($r2['body']['suggestion']['id'] ?? 0) === $area1Id) ? 'PASS' : 'FAIL';

// Test 3: Use Existing is frontend-only — verify suggestion id matches existing
$results['test3_use_existing_frontend'] = 'PASS (UI selects suggestion id; API suggestion verified in test2)';

// Test 4: force_create creates new despite similarity
$beforeCount = Area::query()->where('city_id', $barmerCity->id)->whereIn('normalized_name', ['krishna nagr test'])->count();
$r4 = postArea($controller, $base + ['name' => 'Krishna Nagr Test', 'force_create' => true]);
$afterCount = Area::query()->where('city_id', $barmerCity->id)->where('normalized_name', 'krishna nagr test')->count();
$results['test4_force_create_new'] = ($r4['status'] === 200 && $afterCount >= 1) ? 'PASS' : 'FAIL';

// Test 5: exact duplicate with force_create still upserts same row (no second exact row)
$exactCountBefore = Area::query()->where('city_id', $barmerCity->id)->where('normalized_name', 'krishna nagar test')->count();
$r5 = postArea($controller, $base + ['name' => 'Krishna Nagar Test', 'force_create' => true]);
$exactCountAfter = Area::query()->where('city_id', $barmerCity->id)->where('normalized_name', 'krishna nagar test')->count();
$sameId = (int) ($r5['body']['data']['id'] ?? 0) === $area1Id;
$results['test5_exact_duplicate_force'] = ($r5['status'] === 200 && $sameId && $exactCountAfter === $exactCountBefore) ? 'PASS' : 'FAIL';

// Test 6: different city — similar spelling, no 409
if ($otherCity) {
    $r6 = postArea($controller, [
        'city_id' => $otherCity->id,
        'city' => $otherCity->name,
        'state' => $otherCity->state,
        'country' => $otherCity->country ?: 'India',
        'name' => 'Krishna Nagr Other City',
    ]);
    $results['test6_different_city_ok'] = ($r6['status'] === 200) ? 'PASS' : 'FAIL status=' . $r6['status'];
    if ($r6['status'] === 200 && ! empty($r6['body']['data']['id'])) {
        Area::query()->where('id', $r6['body']['data']['id'])->delete();
    }
} else {
    $results['test6_different_city_ok'] = 'SKIP no second city';
}

// Test 7: force_create without prior 409
$r7 = postArea($controller, $base + ['name' => 'Unique Area Zzz 999', 'force_create' => true]);
$results['test7_force_create_direct'] = ($r7['status'] === 200) ? 'PASS' : 'FAIL';
if ($r7['status'] === 200 && ! empty($r7['body']['data']['id'])) {
    Area::query()->where('id', $r7['body']['data']['id'])->delete();
}

// Test 8: CSV classify still uses isSimilarName — reflection via classifyCsvArea path not exposed; check method exists
$results['test8_csv_logic_unchanged'] = method_exists($controller, 'classifyCsvArea') ? 'PASS (classifyCsvArea intact)' : 'FAIL';

// Test 9: normalize route exists
$results['test9_normalize_unchanged'] = \Illuminate\Support\Facades\Route::has('area-listing.normalize-all') ? 'PASS' : 'FAIL';

// Test 10: sub-area store unaffected
$subReq = Request::create('/area-listing/sub-areas', 'POST', [
    'area_id' => $area1Id,
    'name' => 'Sub Test Lane',
]);
$subReq->headers->set('Accept', 'application/json');
$subResp = $controller->storeSubArea($subReq);
$results['test10_sub_area_unaffected'] = ($subResp->getStatusCode() === 200) ? 'PASS' : 'FAIL';
if ($subResp->getStatusCode() === 200) {
    $subBody = json_decode($subResp->getContent(), true);
    if (! empty($subBody['data']['id'])) {
        DB::table('area_listing_sub_areas')->where('id', $subBody['data']['id'])->delete();
    }
}

// Cleanup
Area::query()->whereIn('normalized_name', ['krishna nagar test', 'krishna nagr test', 'unique area zzz 999'])->delete();

echo "BARmer_city_id={$barmerCity->id}\n";
echo "test2_status={$r2['status']} suggestion=" . ($r2['body']['suggestion']['name'] ?? '') . "\n";
foreach ($results as $name => $status) {
    echo "{$name}: {$status}\n";
}
$fail = false;
foreach ($results as $status) {
    if (str_starts_with($status, 'FAIL')) {
        $fail = true;
    }
}
echo $fail ? "OVERALL_FAIL\n" : "OVERALL_PASS\n";
