<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Udaipur cities ===\n";
$cities = DB::table('area_listing_cities')->whereRaw('LOWER(name) LIKE ?', ['%udaipur%'])->get();
foreach ($cities as $c) {
    echo "city_id={$c->id} name={$c->name} state={$c->state}\n";
}

$cityId = $cities->first()->id ?? 0;
echo "\n=== Recent property locations (Udaipur-related) ===\n";
$rows = DB::table('area_listing_property_locations as l')
    ->leftJoin('area_listing_areas as a', 'a.id', '=', 'l.area_id')
    ->leftJoin('area_listing_sub_areas as s', 's.id', '=', 'l.sub_area_id')
    ->where(function ($q) use ($cityId) {
        $q->where('l.city_id', $cityId)
            ->orWhereRaw('LOWER(l.city) LIKE ?', ['%udaipur%']);
    })
    ->orderByDesc('l.updated_at')
    ->limit(15)
    ->get([
        'l.property_id',
        'l.area_id',
        'l.sub_area_id',
        'l.area_name',
        'l.sub_area_name',
        'l.detected_area_name',
        'l.detected_sub_area_name',
        'a.name as db_area',
        's.name as db_sub',
        'l.updated_at',
    ]);

foreach ($rows as $r) {
    echo json_encode($r) . "\n";
}

echo "\n=== Areas in Udaipur with sub-area counts ===\n";
if ($cityId) {
    $areas = DB::table('area_listing_areas')
        ->where('city_id', $cityId)
        ->where('workflow_status', 'active')
        ->orderBy('name')
        ->limit(20)
        ->get(['id', 'name']);
    foreach ($areas as $a) {
        $subCount = DB::table('area_listing_sub_areas')
            ->where('area_id', $a->id)
            ->where('workflow_status', 'active')
            ->count();
        echo "area {$a->id} {$a->name} subs={$subCount}\n";
    }
}
