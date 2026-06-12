<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Plugins\AreaListing\Services\AreaListingService;

$c = Customer::find(15);
echo json_encode([
    'id' => $c->id,
    'email' => $c->email,
    'is_agent' => $c->is_agent,
    'is_agent_type' => gettype($c->is_agent),
    'can_manage_area_listing' => $c->can_manage_area_listing,
    'userCanAutoCreate' => AreaListingService::userCanAutoCreateAreas($c),
], JSON_PRETTY_PRINT) . "\n";

$ref = new ReflectionMethod(AreaListingService::class, 'userCanAutoCreateAreas');
$start = max(1, $ref->getStartLine() - 2);
$end = $ref->getEndLine() + 2;
$file = file('/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php');
echo "\n--- userCanAutoCreateAreas on server ---\n";
echo implode('', array_slice($file, $start - 1, $end - $start + 1));
