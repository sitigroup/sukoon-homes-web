<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Projects;
use App\Plugins\AreaListing\Models\Area;
use App\Plugins\AreaListing\Services\AreaListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

function locRow(int $projectId): ?object
{
    return DB::table('area_listing_project_locations')->where('project_id', $projectId)->first();
}

$area = Area::query()->where('status', 1)->orderBy('id')->first();

if (! $area) {
    echo "FAIL: need at least one active area\n";
    exit(1);
}

DB::beginTransaction();

$seededProject = false;
$project = Projects::query()->orderBy('id')->first();
if (! $project) {
    $seededProject = true;
    $categoryId = (int) (DB::table('categories')->orderBy('id')->value('id') ?: 0);
    if ($categoryId < 1) {
        echo "FAIL: need a category row to seed a test project\n";
        DB::rollBack();
        exit(1);
    }
    $project = new Projects();
    $project->title = 'AreaListing merge test ' . date('YmdHis');
    $project->slug_id = 'area-listing-merge-test-' . time();
    $project->description = 'Transactional test row';
    $project->city = $area->city_name ?: $area->city;
    $project->state = $area->state;
    $project->country = $area->country ?: 'India';
    $project->latitude = '25.7520000';
    $project->longitude = '71.3960000';
    $project->added_by = 1;
    $project->category_id = $categoryId;
    $project->status = 1;
    $project->request_status = 'approved';
    $project->save();
    echo "SEEDED_PROJECT_ID={$project->id}\n";
}

$originalAreaId = (int) $area->id;
AreaListingService::saveProjectLocation($project, Request::create('/', 'POST', [
    'area_id' => $originalAreaId,
    'city' => $area->city_name ?: $area->city,
    'state' => $area->state,
    'country' => $area->country ?: 'India',
    'latitude' => '25.7520000',
    'longitude' => '71.3960000',
]));

$before = locRow($project->id);
$altAreaId = (int) (Area::query()
    ->where('id', '!=', $originalAreaId)
    ->where('status', 1)
    ->value('id') ?: $originalAreaId);

echo "PROJECT_ID={$project->id}\n";
echo "ORIGINAL_AREA_ID={$originalAreaId}\n";
echo "ALT_AREA_ID={$altAreaId}\n";
echo "BEFORE_LAT={$before->latitude} BEFORE_LNG={$before->longitude}\n";

$results = [];

try {
    // Test 1: explicit area_id preserved
    $req1 = Request::create('/', 'POST', [
        'area_id' => $originalAreaId,
        'latitude' => '25.8000000',
        'longitude' => '71.5000000',
        'city' => $project->city,
        'state' => $project->state,
        'country' => $project->country,
    ]);
    $merged1 = AreaListingService::buildMergedProjectAreaListingRequest($project, $req1);
    AreaListingService::saveProjectLocation($project, $merged1);
    $row1 = locRow($project->id);
    $results['test1_area_preserved'] = (int) $row1->area_id === $originalAreaId ? 'PASS' : 'FAIL got ' . $row1->area_id;
    $results['test1_lat'] = (string) $row1->latitude === '25.8000000' ? 'PASS' : 'FAIL got ' . $row1->latitude;
    $results['test1_lng'] = (string) $row1->longitude === '71.5000000' ? 'PASS' : 'FAIL got ' . $row1->longitude;

    // Test 2: GPS only via location[] — area_id must stay
    $req2 = Request::create('/', 'POST', [
        'location' => [
            'latitude' => '25.7500001',
            'longitude' => '71.4000001',
        ],
        'city' => $project->city,
        'state' => $project->state,
    ]);
    $merged2 = AreaListingService::buildMergedProjectAreaListingRequest($project, $req2);
    $results['test2_merge_has_area'] = (int) $merged2->input('area_id') === $originalAreaId ? 'PASS' : 'FAIL merged area_id=' . $merged2->input('area_id');
    AreaListingService::saveProjectLocation($project, $merged2);
    $row2 = locRow($project->id);
    $results['test2_area_preserved'] = (int) $row2->area_id === $originalAreaId ? 'PASS' : 'FAIL got ' . $row2->area_id;
    $results['test2_lat'] = (string) $row2->latitude === '25.7500001' ? 'PASS' : 'FAIL got ' . $row2->latitude;
    $results['test2_lng'] = (string) $row2->longitude === '71.4000001' ? 'PASS' : 'FAIL got ' . $row2->longitude;

    // Test 3: new area_id when explicitly sent
    if ($altAreaId !== $originalAreaId) {
        $req3 = Request::create('/', 'POST', [
            'area_id' => $altAreaId,
            'latitude' => '25.7600002',
            'longitude' => '71.4100002',
            'city' => $project->city,
            'state' => $project->state,
            'country' => $project->country,
        ]);
        $merged3 = AreaListingService::buildMergedProjectAreaListingRequest($project, $req3);
        AreaListingService::saveProjectLocation($project, $merged3);
        $row3 = locRow($project->id);
        $results['test3_new_area'] = (int) $row3->area_id === $altAreaId ? 'PASS' : 'FAIL got ' . $row3->area_id;
        $results['test3_lat'] = (string) $row3->latitude === '25.7600002' ? 'PASS' : 'FAIL got ' . $row3->latitude;
        $results['test3_lng'] = (string) $row3->longitude === '71.4100002' ? 'PASS' : 'FAIL got ' . $row3->longitude;
    } else {
        $results['test3_new_area'] = 'SKIP (no alternate area)';
        $results['test3_lat'] = 'SKIP';
        $results['test3_lng'] = 'SKIP';
    }

    // Test 4: simulate OLD behavior without merge (GPS + city/state, no area_id — typical API update)
    $reqOld = Request::create('/', 'POST', [
        'location' => ['latitude' => '25.7000003', 'longitude' => '71.3000003'],
        'city' => $project->city,
        'state' => $project->state,
        'country' => $project->country,
    ]);
    AreaListingService::saveProjectLocation($project, $reqOld);
    $rowOld = locRow($project->id);
    $results['test4_without_merge_would_drop'] = empty($rowOld->area_id)
        ? 'CONFIRMED_BUG_WITHOUT_MERGE (area_id cleared)'
        : 'NOTE area_id still ' . $rowOld->area_id;

    // Restore via merge for rollback sanity
    $mergedRestore = AreaListingService::buildMergedProjectAreaListingRequest($project, Request::create('/', 'POST', [
        'area_id' => $originalAreaId,
        'latitude' => $before->latitude,
        'longitude' => $before->longitude,
    ]));
    AreaListingService::saveProjectLocation($project, $mergedRestore);
} finally {
    DB::rollBack();
}

$after = locRow($project->id);
if ($seededProject) {
    $results['test5_rollback_no_row'] = $after === null ? 'PASS' : 'FAIL row still exists after rollback';
} else {
    $results['test5_rollback_area'] = $after && (int) $after->area_id === (int) $before->area_id ? 'PASS' : 'FAIL after=' . ($after->area_id ?? 'null') . ' before=' . $before->area_id;
    $results['test5_rollback_lat'] = $after && (string) $after->latitude === (string) $before->latitude ? 'PASS' : 'FAIL';
    $results['test5_rollback_lng'] = $after && (string) $after->longitude === (string) $before->longitude ? 'PASS' : 'FAIL';
}

foreach ($results as $name => $status) {
    echo "{$name}: {$status}\n";
}

$fail = false;
foreach ($results as $name => $status) {
    if (str_starts_with($status, 'FAIL')) {
        $fail = true;
    }
}
echo $fail ? "OVERALL_FAIL\n" : "OVERALL_PASS\n";
