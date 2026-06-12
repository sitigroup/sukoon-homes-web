<?php
/**
 * Patch AreaListingService: normalize geocoded city names and link master areas to canonical cities.
 * Run on server as root after backup:
 *   php web-fix/patch-area-listing-city-normalize.php
 */
$admin = getenv('ADMIN_ROOT') ?: '/www/wwwroot/admin-homes';
$source = __DIR__ . '/AreaListingService.php';
$target = $admin . '/app/Plugins/AreaListing/Services/AreaListingService.php';

if (! is_file($source)) {
    fwrite(STDERR, "Missing source: {$source}\n");
    exit(1);
}

if (! is_dir(dirname($target))) {
    fwrite(STDERR, "Missing admin plugin dir: {$target}\n");
    exit(1);
}

copy($source, $target);
echo "Copied AreaListingService.php -> {$target}\n";

require $admin . '/vendor/autoload.php';
$app = require $admin . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$repaired = App\Plugins\AreaListing\Services\AreaListingService::repairOrphanMasterAreas();
echo "Repaired orphan master areas: {$repaired}\n";

passthru('cd ' . escapeshellarg($admin) . ' && sudo -u www php artisan optimize:clear');
echo "Done.\n";
