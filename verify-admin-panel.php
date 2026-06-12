<?php

/**
 * Simulates logged-in admin access to Area Wise — no browser required.
 */
$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Plugins\AreaListing\Http\Controllers\Admin\AreaListingAdminController;
use App\Plugins\AreaListing\Models\Area;
use App\Plugins\AreaListing\Models\SubArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

$admin = User::query()->orderBy('id')->first();
if (! $admin) {
    echo "FAIL: no admin user in database\n";
    exit(1);
}
Auth::login($admin);

function adminGet($kernel, string $path): array
{
    $request = Illuminate\Http\Request::create($path, 'GET');
    $request->setUserResolver(fn () => Auth::user());
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    return ['status' => $response->getStatusCode(), 'body' => $response->getContent()];
}

function adminPost($kernel, string $path, array $data = []): array
{
    $request = Illuminate\Http\Request::create($path, 'POST', $data);
    $request->setUserResolver(fn () => Auth::user());
    $response = $kernel->handle($request);
    $kernel->terminate($request, $response);
    return [
        'status' => $response->getStatusCode(),
        'body' => $response->getContent(),
        'json' => json_decode($response->getContent(), true),
    ];
}

$controller = app(AreaListingAdminController::class);
$out = [];

// 1 — area-listing index loads
$page = adminGet($kernel, '/area-listing?city=Barmer&state=Rajasthan&country=India');
$out['1_page_loads'] = ($page['status'] === 200 && str_contains($page['body'], 'Managing')) ? 'PASS' : 'FAIL status=' . $page['status'];
$out['1_tabs'] = (
    str_contains($page['body'], 'Areas') &&
    str_contains($page['body'], 'Sub Areas') &&
    str_contains($page['body'], 'Archived') &&
    str_contains($page['body'], 'Tools')
) ? 'PASS' : 'FAIL';
$out['1_barmer_banner'] = (str_contains($page['body'], 'Barmer')) ? 'PASS' : 'FAIL';

// 2 — CRUD area
$testName = 'Verify Area ' . time();
$store = adminPost($kernel, '/area-listing/areas', [
    '_token' => csrf_token(),
    'name' => $testName,
    'city' => 'Barmer',
    'state' => 'Rajasthan',
    'country' => 'India',
    'city_id' => DB::table('area_listing_cities')->where('name', 'Barmer')->value('id'),
    'status' => 1,
]);
$area = Area::query()->where('name', $testName)->first();
$out['2_add_area'] = ($area && $area->workflow_status === 'active') ? 'PASS id=' . $area->id : 'FAIL';

if ($area) {
    $areaId = $area->id;
    $area->update(['name' => $testName . ' Updated']);
    $out['2_edit_area'] = Area::find($areaId)->name === $testName . ' Updated' ? 'PASS' : 'FAIL';

    $controller->destroyArea($area);
    $archived = Area::find($areaId);
    $out['2_archive_or_delete'] = $archived
        ? ($archived->workflow_status === 'archived' ? 'PASS archived' : 'FAIL')
        : 'PASS hard-deleted (unused area)';
    if ($archived && $archived->workflow_status === 'archived') {
        $out['2_in_archived_list'] = str_contains(adminGet($kernel, '/area-listing?city=Barmer')['body'], $testName . ' Updated') ? 'PASS' : 'FAIL';
        $controller->restoreArea($archived);
        $out['5_restore'] = Area::find($areaId)->workflow_status === 'active' ? 'PASS' : 'FAIL';
        $archived->forceDelete();
    }
}

// 3 — sub area
$parent = Area::query()->active()->where('city_id', DB::table('area_listing_cities')->where('name', 'Barmer')->value('id'))->first();
if ($parent) {
    $subName = 'Verify Sub ' . time();
    adminPost($kernel, '/area-listing/sub-areas', [
        '_token' => csrf_token(),
        'area_id' => $parent->id,
        'name' => $subName,
    ]);
    $sub = SubArea::query()->where('name', $subName)->first();
    $out['3_add_sub'] = $sub ? 'PASS' : 'FAIL';
    if ($sub) {
        $subId = $sub->id;
        $sub->update(['name' => $subName . ' Edited']);
        $out['3_edit_sub'] = SubArea::find($subId)->name === $subName . ' Edited' ? 'PASS' : 'FAIL';
        $controller->destroySubArea($sub);
        $archSub = SubArea::find($subId);
        $out['3_archive_sub'] = $archSub
            ? ($archSub->workflow_status === 'archived' ? 'PASS' : 'FAIL')
            : 'PASS hard-deleted';
        if ($archSub) {
            $archSub->forceDelete();
        }
    }
} else {
    $out['3_sub'] = 'SKIP no parent area';
}

// 5 archived age in HTML
$old = Area::query()->create([
    'name' => 'Old Archived ' . time(),
    'slug' => 'old-arch-' . time(),
    'normalized_name' => 'old archived ' . time(),
    'city' => 'Barmer',
    'city_name' => 'Barmer',
    'state' => 'Rajasthan',
    'country' => 'India',
    'city_id' => DB::table('area_listing_cities')->where('name', 'Barmer')->value('id'),
    'status' => false,
    'workflow_status' => 'archived',
    'archived_at' => now()->subDays(95),
]);
$archPage = adminGet($kernel, '/area-listing?city=Barmer');
$out['5_90_badge'] = str_contains($archPage['body'], '90+') ? 'PASS' : 'FAIL';
$out['5_age_filter_ui'] = (str_contains($archPage['body'], '30+ days') && str_contains($archPage['body'], 'archived-area-age-filter')) ? 'PASS' : 'FAIL';
$old->forceDelete();

// 6 tools endpoints
$repair = json_decode($controller->repairLocationsDryRun()->getContent(), true);
$drift = json_decode($controller->checkCityDrift()->getContent(), true);
$out['6_repair'] = ((int) ($repair['missing'] ?? -1) === 0) ? 'PASS' : 'FAIL';
$out['6_drift'] = ((int) ($drift['total_drift'] ?? -1) === 0) ? 'PASS' : 'FAIL';
$out['6_normalize_route'] = Illuminate\Support\Facades\Route::has('area-listing.normalize-all') ? 'PASS' : 'FAIL';

// areasData active
$activeData = json_decode($controller->areasData(Request::create('/', 'GET', ['city' => 'Barmer', 'state' => 'Rajasthan']))->getContent(), true);
$out['2_areas_listed'] = (! empty($activeData['rows'])) ? 'PASS count=' . count($activeData['rows']) : 'FAIL';

foreach ($out as $k => $v) {
    echo "{$k}: {$v}\n";
}
