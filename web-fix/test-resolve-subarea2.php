<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Services\AreaListingService;

$tests = [
    'sub_only' => [
        ['name' => 'Rai Colony Road', 'types' => ['sublocality_level_2']],
    ],
    'area_then_sub' => [
        ['name' => 'Krishna Nagar', 'types' => ['sublocality_level_1']],
        ['name' => 'Rai Colony Road', 'types' => ['sublocality_level_2']],
    ],
    'sub_then_area' => [
        ['name' => 'Rai Colony Road', 'types' => ['sublocality_level_2']],
        ['name' => 'Krishna Nagar', 'types' => ['sublocality_level_1']],
    ],
];

foreach ($tests as $label => $components) {
    $result = AreaListingService::resolveNearestFromCoordinates(24.58, 73.71, 1, 'Udaipur', 'Rajasthan', 'India', $components);
    echo $label . ': sub_area_id=' . ($result['sub_area_id'] ?? 'null') . ' area_id=' . ($result['area_id'] ?? 'null') . "\n";
}
