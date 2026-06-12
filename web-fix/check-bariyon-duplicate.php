<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$needle = 'bariyon ka vas';

echo "=== Master sub-areas named Bariyon Ka Vas ===\n";
$subs = DB::table('area_listing_sub_areas as s')
    ->join('area_listing_areas as a', 'a.id', '=', 's.area_id')
    ->whereRaw('LOWER(TRIM(s.name)) = ?', [$needle])
    ->get(['s.id', 's.name', 's.area_id', 'a.name as area_name', 'a.city', 's.workflow_status']);

foreach ($subs as $row) {
    echo json_encode($row) . "\n";
}

echo "\n=== Master areas Rai Colony Road / Krishna Nagar in Barmer ===\n";
$areas = DB::table('area_listing_areas')
    ->where('city', 'Barmer')
    ->where(function ($q) {
        $q->whereRaw('LOWER(TRIM(name)) = ?', ['rai colony road'])
            ->orWhereRaw('LOWER(TRIM(name)) = ?', ['krishna nagar']);
    })
    ->get(['id', 'name', 'city', 'workflow_status']);

foreach ($areas as $row) {
    echo json_encode($row) . "\n";
    $areaSubs = DB::table('area_listing_sub_areas')
        ->where('area_id', $row->id)
        ->whereRaw('LOWER(TRIM(name)) = ?', [$needle])
        ->get(['id', 'name', 'area_id', 'workflow_status']);
    foreach ($areaSubs as $sub) {
        echo "  sub: " . json_encode($sub) . "\n";
    }
}

echo "\n=== Listings with Bariyon Ka Vas (property) ===\n";
$props = DB::table('area_listing_property_locations as l')
    ->leftJoin('propertys as p', 'p.id', '=', 'l.property_id')
    ->where(function ($q) use ($needle) {
        $q->whereRaw('LOWER(TRIM(l.detected_sub_area_name)) = ?', [$needle])
            ->orWhereRaw('LOWER(TRIM(l.sub_area_name)) = ?', [$needle]);
    })
    ->get(['l.property_id', 'p.title', 'l.area_id', 'l.area_name', 'l.detected_area_name', 'l.sub_area_id', 'l.sub_area_name', 'l.detected_sub_area_name']);

foreach ($props as $row) {
    echo json_encode($row) . "\n";
}

echo "\n=== Listings with Bariyon Ka Vas (project) ===\n";
$projs = DB::table('area_listing_project_locations as l')
    ->leftJoin('projects as p', 'p.id', '=', 'l.project_id')
    ->where(function ($q) use ($needle) {
        $q->whereRaw('LOWER(TRIM(l.detected_sub_area_name)) = ?', [$needle])
            ->orWhereRaw('LOWER(TRIM(l.sub_area_name)) = ?', [$needle]);
    })
    ->get(['l.project_id', 'p.title', 'l.area_id', 'l.area_name', 'l.detected_area_name', 'l.sub_area_id', 'l.sub_area_name', 'l.detected_sub_area_name']);

foreach ($projs as $row) {
    echo json_encode($row) . "\n";
}

echo "\n=== Sub-area id 59 ===\n";
$s59 = DB::table('area_listing_sub_areas')->where('id', 59)->first();
echo json_encode($s59) . "\n";
if ($s59) {
    $parent = DB::table('area_listing_areas')->where('id', $s59->area_id)->first();
    echo "parent: " . json_encode($parent) . "\n";
}

echo "\n=== Rows id 2 and 59 in area_listing_areas ===\n";
foreach ([2, 59] as $id) {
    $a = DB::table('area_listing_areas')->where('id', $id)->first();
    echo "area {$id}: " . json_encode($a) . "\n";
    if ($a) {
        $s = DB::table('area_listing_sub_areas')->where('area_id', $id)->get(['id', 'name', 'workflow_status']);
        foreach ($s as $sub) {
            echo "  " . json_encode($sub) . "\n";
        }
    }
}

echo "\n=== Suggestions for Bariyon / Krishna Nagar ===\n";
$suggestions = DB::table('area_listing_suggestions')
    ->where(function ($q) {
        $q->whereRaw('LOWER(TRIM(name)) = ?', ['bariyon ka vas'])
            ->orWhereRaw('LOWER(TRIM(name)) = ?', ['krishna nagar']);
    })
    ->orderBy('id')
    ->get(['id', 'type', 'name', 'area_id', 'status', 'normalized_name']);
foreach ($suggestions as $row) {
    echo json_encode($row) . "\n";
}

echo "\n=== Sub-areas under Krishna Nagar (area 45) ===\n";
$knSubs = DB::table('area_listing_sub_areas')->where('area_id', 45)->get(['id', 'name', 'workflow_status']);
foreach ($knSubs as $sub) {
    echo json_encode($sub) . "\n";
}
