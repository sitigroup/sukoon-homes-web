<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Plugins\AreaListing\Services\AreaListingService;

$property = Property::orderByDesc('id')->first();

if (! $property) {
    echo "no property\n";
    exit(1);
}

$row = $property->toArray();
$guest = AreaListingService::decoratePropertyResponse($row, $property, null);
$owner = AreaListingService::decoratePropertyResponse($row, $property, (int) $property->added_by);

echo "property_id={$property->id} added_by={$property->added_by}\n\n";

echo "=== GUEST (privacy on) ===\n";
echo 'address=' . ($guest['address'] ?? '') . "\n";
echo 'client_address=' . (isset($guest['client_address']) ? ($guest['client_address'] ?: 'null') : 'unset') . "\n";
echo 'latitude=' . (isset($guest['latitude']) ? ($guest['latitude'] ?: 'null') : 'unset') . "\n";
echo 'full_address=' . (isset($guest['area_listing']['full_address']) ? ($guest['area_listing']['full_address'] ?: 'null') : 'unset') . "\n";
echo 'manual_address=' . (isset($guest['area_listing']['manual_address']) ? ($guest['area_listing']['manual_address'] ?: 'null') : 'unset') . "\n";
echo 'can_view_exact=' . (! empty($guest['can_view_exact_location']) ? '1' : '0') . "\n";

echo "\n=== OWNER (full address) ===\n";
echo 'address=' . ($owner['address'] ?? '') . "\n";
echo 'client_address=' . ($owner['client_address'] ?? 'null') . "\n";
echo 'full_address=' . ($owner['area_listing']['full_address'] ?? 'null') . "\n";
echo 'manual_address=' . ($owner['area_listing']['manual_address'] ?? 'null') . "\n";
echo 'can_view_exact=' . (! empty($owner['can_view_exact_location']) ? '1' : '0') . "\n";

$privacyOk = empty($guest['can_view_exact_location'])
    && empty($guest['area_listing']['full_address'])
    && empty($guest['latitude']);
$ownerOk = ! empty($owner['can_view_exact_location']);

echo "\nRESULT privacy_ok=" . ($privacyOk ? 'YES' : 'NO') . " owner_ok=" . ($ownerOk ? 'YES' : 'NO') . "\n";
