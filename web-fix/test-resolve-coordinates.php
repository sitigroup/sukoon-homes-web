<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Services\AreaListingService;

// Central Area, Udaipur approximate pin
$lat = 24.5718988;
$lng = 73.7103079;
$components = [
    ['name' => 'Central Area', 'types' => ['sublocality_level_1', 'political']],
    ['name' => 'Udaipur', 'types' => ['locality', 'political']],
    ['name' => 'Rajasthan', 'types' => ['administrative_area_level_1', 'political']],
    ['name' => 'India', 'types' => ['country', 'political']],
];

$result = AreaListingService::resolveNearestFromCoordinates($lat, $lng, null, 'Udaipur', 'Rajasthan', 'India', $components);

echo json_encode([
    'test' => 'Central Area Udaipur',
    'match' => $result,
], JSON_PRETTY_PRINT) . "\n";
