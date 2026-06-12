<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$p = \App\Models\Property::where('title', 'like', '%plot%barmer%')->orderByDesc('id')->first();
if (! $p) {
    exit(1);
}

require_once '/www/wwwroot/admin-homes/app/Helpers/custom_helper.php';

$row = $p->toArray();
$guest = \App\Plugins\AreaListing\Services\AreaListingService::decoratePropertyResponse($row, $p, null);
$owner = \App\Plugins\AreaListing\Services\AreaListingService::decoratePropertyResponse($row, $p, (int) $p->added_by);

echo "=== GUEST (public) ===\n";
echo 'address=' . ($guest['address'] ?? '') . "\n";
echo 'area_listing.area=' . ($guest['area_listing']['area_name'] ?? '') . "\n";
echo 'area_listing.sub=' . ($guest['area_listing']['sub_area_name'] ?? '') . "\n";
echo 'full_address=' . ($guest['area_listing']['full_address'] ?? 'null') . "\n";
echo 'can_view_exact=' . ($guest['can_view_exact_location'] ? '1' : '0') . "\n";

echo "=== OWNER ===\n";
echo 'address=' . ($owner['address'] ?? '') . "\n";
echo 'full_address=' . ($owner['area_listing']['full_address'] ?? '') . "\n";
echo 'manual_address=' . ($owner['area_listing']['manual_address'] ?? '') . "\n";
echo 'area=' . ($owner['area_listing']['area_name'] ?? '') . "\n";
echo 'sub=' . ($owner['area_listing']['sub_area_name'] ?? '') . "\n";
echo 'can_view_exact=' . ($owner['can_view_exact_location'] ? '1' : '0') . "\n";
