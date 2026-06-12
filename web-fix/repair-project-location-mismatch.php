<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Projects;
use App\Plugins\AreaListing\Services\AreaListingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$projectId = (int) ($argv[1] ?? 7);
$row = DB::table('area_listing_project_locations')->where('project_id', $projectId)->first();
$project = Projects::find($projectId);
if (! $row || ! $project) {
    echo "missing project/location\n";
    exit(1);
}

$detectedArea = trim((string) ($row->detected_area_name ?: ''));
$detectedSub = trim((string) ($row->detected_sub_area_name ?: ''));
$areaId = $row->area_id;
$subId = $row->sub_area_id;

if ($detectedArea !== '' && $areaId) {
    $linked = DB::table('area_listing_areas')->where('id', $areaId)->value('name');
    if ($linked && strtolower(trim($linked)) !== strtolower($detectedArea)) {
        $areaId = null;
        $subId = null;
    }
}
if ($detectedSub !== '' && $subId) {
    $linkedSub = DB::table('area_listing_sub_areas')->where('id', $subId)->value('name');
    if ($linkedSub && strtolower(trim($linkedSub)) !== strtolower($detectedSub)) {
        $subId = null;
    }
}

$payload = [
    'area_listing_user_portal' => 1,
    'city_id' => $row->city_id,
    'state_id' => $row->state_id,
    'city' => $row->city,
    'state' => $row->state,
    'country' => $row->country ?? 'India',
    'area_id' => $areaId,
    'sub_area_id' => $subId,
    'detected_area_name' => $detectedArea,
    'detected_sub_area_name' => $detectedSub,
    'area_name' => $detectedArea,
    'sub_area_name' => $detectedSub,
    'address' => $row->full_address,
    'client_address' => $row->manual_address,
    'latitude' => $row->latitude,
    'longitude' => $row->longitude,
    'area_listing_source' => $row->source ?? 'manual',
];

AreaListingService::saveProjectLocation($project, Request::create('/', 'POST', $payload));

$row2 = DB::table('area_listing_project_locations')->where('project_id', $projectId)->first();
echo "repaired project {$projectId}\n";
echo "area_id={$row2->area_id} sub={$row2->sub_area_id}\n";
echo "area_name={$row2->area_name} sub_name={$row2->sub_area_name}\n";
