<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Plugins\AreaListing\Services\AreaListingService;

$email = $argv[1] ?? 'hemssarda@gmail.com';
$c = Customer::where('email', $email)->first();

if (!$c) {
    echo "NOT_FOUND: {$email}\n";
    exit(1);
}

$canManage = AreaListingService::userCanAutoCreateAreas($c);

echo json_encode([
    'id' => $c->id,
    'email' => $c->email,
    'name' => $c->name,
    'is_agent' => (bool) $c->is_agent,
    'is_agent_verified' => (bool) $c->is_agent_verified,
    'can_manage_area_listing' => (bool) $c->can_manage_area_listing,
    'userCanAutoCreateAreas' => $canManage,
], JSON_PRETTY_PRINT) . "\n";
