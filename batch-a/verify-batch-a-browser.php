<?php

/**
 * Batch A backend verification (logged-in session substitute when browser blocked).
 * Run: php /tmp/verify-batch-a-browser.php
 */

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Http\Controllers\Admin\AreaListingAdminController;
use App\Plugins\AreaListing\Models\Area;
use App\Plugins\AreaListing\Models\SubArea;
use App\Plugins\AreaListing\Services\AreaListingService;
use App\Models\Projects;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$out = [];

// --- 1) Sub-area similarity ---
$sub = SubArea::query()->where('status', 1)->orderByDesc('id')->first();
if (! $sub) {
    $out['1_sub_area_similar'] = 'SKIP no sub-area';
} else {
    $typoName = rtrim($sub->name) . 'x';
    if (strlen($typoName) > 255) {
        $typoName = substr($sub->name, 0, -1) . 'e';
    }
    $req = Request::create('/area-listing/sub-areas', 'POST', [
        'area_id' => $sub->area_id,
        'name' => $typoName,
    ]);
    $req->headers->set('Accept', 'application/json');
    $resp = app(AreaListingAdminController::class)->storeSubArea($req);
    $out['1_similar_409'] = $resp->getStatusCode() === 409 ? 'PASS' : 'FAIL code=' . $resp->getStatusCode();

    $reqForce = Request::create('/area-listing/sub-areas', 'POST', [
        'area_id' => $sub->area_id,
        'name' => 'Batch A Verify Sub ' . time(),
        'force_create' => true,
    ]);
    $reqForce->headers->set('Accept', 'application/json');
    $respForce = app(AreaListingAdminController::class)->storeSubArea($reqForce);
    $data = json_decode($respForce->getContent(), true);
    $newId = $data['data']['id'] ?? null;
    $out['1_force_create'] = $respForce->isSuccessful() && $newId ? 'PASS id=' . $newId : 'FAIL';

    if ($newId) {
        SubArea::where('id', $newId)->delete();
        $out['1_cleanup'] = 'PASS deleted test sub';
    }
}

// --- 2) Project GPS merge ---
$locRow = DB::table('area_listing_project_locations')->whereNotNull('area_id')->orderByDesc('project_id')->first();
if (! $locRow) {
    $out['2_project_gps'] = 'SKIP no project location';
} else {
    $project = Projects::find($locRow->project_id);
    $before = [
        'area_id' => $locRow->area_id,
        'sub_area_id' => $locRow->sub_area_id,
        'latitude' => $locRow->latitude,
        'longitude' => $locRow->longitude,
    ];
    $merged = AreaListingService::buildMergedProjectAreaListingRequest(
        $project,
        Request::create('/', 'POST', ['title' => 'Batch A GPS probe'])
    );
    $mergedHasLat = $merged->filled('latitude') || $merged->filled('location');
    $out['2_merge_preserves'] = $mergedHasLat ? 'PASS' : 'FAIL';

    AreaListingService::saveProjectLocation($project, $merged);
    $after = DB::table('area_listing_project_locations')->where('project_id', $project->id)->first();
    $unchanged = (string) $after->area_id === (string) $before['area_id']
        && (string) ($after->sub_area_id ?? '') === (string) ($before['sub_area_id'] ?? '')
        && (string) $after->latitude === (string) $before['latitude']
        && (string) $after->longitude === (string) $before['longitude'];
    $out['2_save_unchanged'] = $unchanged ? 'PASS project_id=' . $project->id : 'FAIL';
}

// --- 3) city_id on inline area ---
$area = Area::query()->where('status', 1)->whereNotNull('city_id')->first();
if (! $area) {
    $out['3_city_id'] = 'SKIP';
} else {
    $req = Request::create('/area-listing/areas', 'POST', [
        'name' => 'Batch A CityId ' . time(),
        'city' => $area->city_name ?: $area->city,
        'state' => $area->state,
        'country' => $area->country ?: 'India',
        'force_create' => true,
    ]);
    $req->headers->set('Accept', 'application/json');
    $resp = app(AreaListingAdminController::class)->storeArea($req);
    $data = json_decode($resp->getContent(), true);
    $newAreaId = $data['data']['id'] ?? null;
    $row = $newAreaId ? Area::find($newAreaId) : null;
    $out['3_city_id_set'] = ($row && $row->city_id) ? 'PASS city_id=' . $row->city_id : 'FAIL';
    if ($newAreaId && $row && ! DB::table('area_listing_property_locations')->where('area_id', $newAreaId)->exists()) {
        $row->delete();
        $out['3_cleanup'] = 'PASS';
    }
}

foreach ($out as $k => $v) {
    echo $k . ': ' . $v . PHP_EOL;
}
