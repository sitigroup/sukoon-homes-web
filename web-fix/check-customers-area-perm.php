<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Plugins\AreaListing\Services\AreaListingService;

$rows = Customer::query()
    ->whereIn('email', ['hemssarda@gmail.com'])
    ->orWhere('is_agent', 0)
    ->orderByDesc('is_agent')
    ->limit(8)
    ->get(['id', 'email', 'is_agent', 'can_manage_area_listing']);

foreach ($rows as $c) {
    echo json_encode([
        'id' => $c->id,
        'email' => $c->email,
        'is_agent' => (int) $c->is_agent,
        'can_manage_area_listing' => (int) ($c->can_manage_area_listing ?? 0),
        'userCanAutoCreate' => AreaListingService::userCanAutoCreateAreas($c),
    ], JSON_UNESCAPED_SLASHES) . PHP_EOL;
}
