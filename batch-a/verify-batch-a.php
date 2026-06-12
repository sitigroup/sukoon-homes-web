<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Plugins\AreaListing\Models\Area;
use App\Plugins\AreaListing\Models\SubArea;
use App\Plugins\AreaListing\Services\AreaListingService;
use App\Models\Projects;
use Illuminate\Http\Request;

$results = [];

// 1) Sub-area similarity helper exists
$controller = new ReflectionClass(\App\Plugins\AreaListing\Http\Controllers\Admin\AreaListingAdminController::class);
$results['sub_area_similar_method'] = $controller->hasMethod('getSimilarExistingSubArea') ? 'PASS' : 'FAIL';

// 2) resolveAreaContext sets city_id when city name matches
$area = Area::query()->where('status', 1)->whereNotNull('city_id')->first();
if ($area) {
    $req = Request::create('/', 'POST', [
        'name' => 'Batch A Test Area ' . time(),
        'city' => $area->city_name ?: $area->city,
        'state' => $area->state,
        'country' => $area->country,
    ]);
    $ctrl = app(\App\Plugins\AreaListing\Http\Controllers\Admin\AreaListingAdminController::class);
    $method = new ReflectionMethod($ctrl, 'resolveAreaContext');
    $method->setAccessible(true);
    $ctx = $method->invoke($ctrl, $req);
    $results['resolve_city_id'] = ! empty($ctx['city_id']) ? 'PASS' : 'FAIL';
} else {
    $results['resolve_city_id'] = 'SKIP no area';
}

// 3) Project merge preserves lat when omitted on update
$projectId = \Illuminate\Support\Facades\DB::table('area_listing_project_locations')
    ->orderByDesc('project_id')
    ->value('project_id');
$project = $projectId ? Projects::query()->find($projectId) : Projects::query()->first();
if ($project) {
    $loc = \Illuminate\Support\Facades\DB::table('area_listing_project_locations')
        ->where('project_id', $project->id)->first();
    $merged = AreaListingService::buildMergedProjectAreaListingRequest(
        $project,
        Request::create('/', 'POST', ['title' => 'noop'])
    );
    $mergedLat = $merged->input('latitude');
    $results['project_merge_lat'] = ($loc && $mergedLat !== null && $mergedLat !== '')
        ? 'PASS'
        : ($loc ? 'FAIL lat=' . var_export($mergedLat, true) : 'SKIP no loc');
} else {
    $results['project_merge'] = 'SKIP no project';
}

// 4) Sub-area similar detection on known pair (if any sub-area exists)
$sub = SubArea::query()->where('status', 1)->first();
if ($sub) {
    $method = new ReflectionMethod($ctrl ?? app(\App\Plugins\AreaListing\Http\Controllers\Admin\AreaListingAdminController::class), 'getSimilarExistingSubArea');
    $method->setAccessible(true);
    $norm = strtolower(preg_replace('/[^a-z0-9]+/', '', $sub->name));
    $typo = substr($norm, 0, -1) . (substr($norm, -1) === 'a' ? 'e' : 'a');
    $similar = $method->invoke($ctrl, (int) $sub->area_id, $typo);
    $results['sub_area_typo_match'] = $similar ? 'PASS' : 'WARN no typo match';
} else {
    $results['sub_area_typo_match'] = 'SKIP no sub-area';
}

// 5) ProjectController update uses merge
$pc = file_get_contents('/www/wwwroot/admin-homes/app/Http/Controllers/ProjectController.php');
$results['project_controller_merge'] = strpos($pc, 'buildMergedProjectAreaListingRequest') !== false ? 'PASS' : 'FAIL';

foreach ($results as $k => $v) {
    echo $k . ': ' . $v . PHP_EOL;
}
