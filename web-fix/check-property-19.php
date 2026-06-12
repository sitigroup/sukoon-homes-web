<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$loc = DB::table('area_listing_property_locations')->where('property_id', 19)->first();
echo "property 19 location:\n" . json_encode($loc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

$suggestions = DB::table('area_listing_suggestions')->where('status', 'pending')->orderByDesc('id')->limit(15)->get();
echo "pending suggestions:\n";
foreach ($suggestions as $s) {
    echo json_encode($s, JSON_UNESCAPED_UNICODE) . "\n";
}

if ($loc && $loc->city_id && $loc->detected_area_name) {
    $normalized = strtolower(preg_replace('/\s+/', ' ', trim($loc->detected_area_name)));
    $areas = DB::table('area_listing_areas')->where('city_id', $loc->city_id)->where('status', 1)->get(['id', 'name', 'normalized_name']);
    echo "\nareas in city {$loc->city_id} matching detected:\n";
    foreach ($areas as $a) {
        if (stripos($a->name, $loc->detected_area_name) !== false || stripos($loc->detected_area_name, $a->name) !== false) {
            echo "  match? id={$a->id} name={$a->name} norm={$a->normalized_name}\n";
        }
    }
}
