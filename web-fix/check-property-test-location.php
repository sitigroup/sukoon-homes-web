<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$slug = $argv[1] ?? 'test';
$prop = DB::table('propertys')->where('slug_id', $slug)->orWhere('slug', $slug)->first();
if (!$prop) {
    echo "Property not found\n";
    exit(1);
}
$loc = DB::table('property_area_listing_locations')->where('property_id', $prop->id)->first();
echo json_encode([
    'property_id' => $prop->id,
    'city' => $prop->city ?? null,
    'location' => [
        'city' => $prop->city ?? null,
        'state' => $prop->state ?? null,
    ],
    'area_listing' => $loc ? (array) $loc : null,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
