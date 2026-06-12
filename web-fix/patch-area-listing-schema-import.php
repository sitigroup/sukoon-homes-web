<?php
/**
 * Fix post_property 500: missing Schema facade import in AreaListingService.
 * Run on server: sudo -u www php web-fix/patch-area-listing-schema-import.php
 */
$path = is_file(__DIR__ . '/../app/Plugins/AreaListing/Services/AreaListingService.php')
    ? __DIR__ . '/../app/Plugins/AreaListing/Services/AreaListingService.php'
    : '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';

if (! is_file($path)) {
    fwrite(STDERR, "File not found: {$path}\n");
    exit(1);
}

$content = file_get_contents($path);
$needle = 'use Illuminate\Support\Facades\Schema;';

if (str_contains($content, $needle)) {
    echo "Already patched.\n";
    exit(0);
}

$insertAfter = 'use Illuminate\Support\Facades\Log;';
if (! str_contains($content, $insertAfter)) {
    fwrite(STDERR, "Anchor line not found.\n");
    exit(1);
}

$content = str_replace(
    $insertAfter,
    $insertAfter . "\nuse Illuminate\Support\Facades\Schema;",
    $content
);

file_put_contents($path, $content);
echo "Patched: {$path}\n";
