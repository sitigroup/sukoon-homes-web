<?php

/**
 * Category-wise cache TTL smoke test for property 12.
 * Run on VPS: php /tmp/test-nearby-category-ttl.php
 */

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\NearbyPlaces\Models\NearbyCategory;
use App\Plugins\NearbyPlaces\Models\NearbyPlaceCache;
use App\Plugins\NearbyPlaces\Services\NearbyPlacesService;
use App\Plugins\NearbyPlaces\Support\NearbyPlacesSettings;
use Illuminate\Support\Facades\DB;

$propertyId = 12;
$service = app(NearbyPlacesService::class);

$restaurant = NearbyCategory::query()->where('slug', 'restaurant')->first();
$hospital = NearbyCategory::query()->where('slug', 'hospital')->first();

if (!$restaurant || !$hospital) {
    fwrite(STDERR, "Missing restaurant or hospital category\n");
    exit(1);
}

echo "Global TTL hours: " . NearbyPlacesSettings::cacheTtlHours() . "\n";

$originalRestaurantTtl = $restaurant->cache_ttl_hours;
$restaurant->update(['cache_ttl_hours' => 1]);
echo "Set restaurant TTL to 1 hour\n";

// Warm cache for all categories first.
$service->getForProperty($propertyId, true, false, null);

$restaurantBefore = NearbyPlaceCache::query()
    ->where('property_id', $propertyId)
    ->where('nearby_category_id', $restaurant->id)
    ->value('fetched_at');
$hospitalBefore = NearbyPlaceCache::query()
    ->where('property_id', $propertyId)
    ->where('nearby_category_id', $hospital->id)
    ->value('fetched_at');

echo "Restaurant fetched_at before category refresh: {$restaurantBefore}\n";
echo "Hospital fetched_at before category refresh: {$hospitalBefore}\n";

sleep(2);

// Category-scoped refresh should only touch restaurant.
$service->getForProperty($propertyId, true, false, (int) $restaurant->id);

$restaurantAfter = NearbyPlaceCache::query()
    ->where('property_id', $propertyId)
    ->where('nearby_category_id', $restaurant->id)
    ->value('fetched_at');
$hospitalAfter = NearbyPlaceCache::query()
    ->where('property_id', $propertyId)
    ->where('nearby_category_id', $hospital->id)
    ->value('fetched_at');

echo "Restaurant fetched_at after category refresh: {$restaurantAfter}\n";
echo "Hospital fetched_at after category refresh: {$hospitalAfter}\n";

$restaurantChanged = $restaurantBefore !== $restaurantAfter;
$hospitalUnchanged = $hospitalBefore === $hospitalAfter;

echo "Restaurant refreshed only: " . ($restaurantChanged ? 'PASS' : 'FAIL') . "\n";
echo "Hospital remained cached: " . ($hospitalUnchanged ? 'PASS' : 'FAIL') . "\n";

// Global fallback when category TTL is null.
$hospital->update(['cache_ttl_hours' => null]);
$hospital->refresh();
$fallbackHours = $hospital->effectiveCacheTtlHours();
echo "Hospital fallback TTL hours: {$fallbackHours} (expected global)\n";
echo "Global fallback works: " . ($fallbackHours === NearbyPlacesSettings::cacheTtlHours() ? 'PASS' : 'FAIL') . "\n";

// Verify expires_at uses category TTL on fetch.
$service->clearCacheForPropertyCategory($propertyId, (int) $restaurant->id);
$service->getForProperty($propertyId, false, false, null);
$expiresAt = NearbyPlaceCache::query()
    ->where('property_id', $propertyId)
    ->where('nearby_category_id', $restaurant->id)
    ->value('expires_at');
$expectedHours = 1;
$diffHours = $expiresAt ? now()->diffInHours($expiresAt, false) : -1;
echo "Restaurant expires_at diff hours (~{$expectedHours}): {$diffHours}\n";
echo "Category TTL applied on write: " . (abs($diffHours - $expectedHours) <= 1 ? 'PASS' : 'FAIL') . "\n";

// Clear all still works.
$beforeAll = (int) NearbyPlaceCache::query()->where('property_id', $propertyId)->count();
$service->clearAllCache();
$afterAll = (int) NearbyPlaceCache::query()->where('property_id', $propertyId)->count();
echo "Clear all cache: before={$beforeAll} after={$afterAll} " . ($beforeAll > 0 && $afterAll === 0 ? 'PASS' : 'FAIL') . "\n";

$restaurant->update(['cache_ttl_hours' => $originalRestaurantTtl]);

echo "done\n";
