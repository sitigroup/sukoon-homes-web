<?php

declare(strict_types=1);

require '/www/wwwroot/admin-homes/vendor/autoload.php';

$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$propertyId = (int) ($argv[1] ?? 12);
$service = $app->make(App\Plugins\NearbyPlaces\Services\NearbyPlacesService::class);

$cleared = $service->clearCacheForProperty($propertyId);
$service->getForProperty($propertyId, true);

echo json_encode([
    'property_id' => $propertyId,
    'cleared' => $cleared,
    'refreshed' => true,
], JSON_PRETTY_PRINT) . PHP_EOL;
