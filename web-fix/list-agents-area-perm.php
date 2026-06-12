<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Plugins\AreaListing\Services\AreaListingService;

$agents = Customer::query()
    ->where('is_agent', 1)
    ->orderBy('id')
    ->get(['id', 'email', 'can_manage_area_listing']);

foreach ($agents as $a) {
    echo json_encode([
        'id' => $a->id,
        'email' => $a->email,
        'can_manage_area_listing' => (int) ($a->can_manage_area_listing ?? 0),
        'userCanAutoCreate' => AreaListingService::userCanAutoCreateAreas($a),
    ], JSON_UNESCAPED_SLASHES) . "\n";
}
