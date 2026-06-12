<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Projects;
use App\Plugins\AreaListing\Services\AreaListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$project = Projects::query()->whereIn('id', function ($q) {
    $q->select('project_id')->from('area_listing_project_locations')->whereNotNull('area_id')->where('area_id', '>', 0);
})->first();

if (! $project) {
    echo "s11_skip: no project with area_id\n";
    exit(0);
}

$before = DB::table('area_listing_project_locations')->where('project_id', $project->id)->first();
$merged = AreaListingService::buildMergedProjectAreaListingRequest($project, Request::create('/', 'POST', [
    'location' => ['latitude' => '25.9990001', 'longitude' => '71.9990001'],
    'city' => $project->city,
    'state' => $project->state,
]));
AreaListingService::saveProjectLocation($project, $merged);
$after = DB::table('area_listing_project_locations')->where('project_id', $project->id)->first();

$ok = (int) $after->area_id === (int) $before->area_id
    && (string) $after->latitude === '25.9990001';
echo "s11_project_area_preserved: " . ($ok ? 'PASS' : 'FAIL before_area=' . $before->area_id . ' after=' . $after->area_id) . "\n";

// rollback lat
DB::table('area_listing_project_locations')->where('project_id', $project->id)->update([
    'latitude' => $before->latitude,
    'longitude' => $before->longitude,
]);
