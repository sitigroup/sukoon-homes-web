<?php
/**
 * OPTIONAL — registers TrustVerificationServiceProvider in config/app.php.
 *
 * wrteam CORE — run only after:
 *   1. Fresh backup: bash scripts/final-sukoon-complete-backup.sh
 *   2. User explicitly approved this one-line hook (same idea as AreaListing provider)
 *
 *   cd /www/wwwroot/admin-homes
 *   php /path/to/cursr/web-fix/patch-trust-verification-register.php
 *
 * Plugin-only alternative: if wrteam already auto-loads app/Plugins ServiceProviders, skip this.
 */
$configPath = '/www/wwwroot/admin-homes/config/app.php';
if (!is_file($configPath)) {
    fwrite(STDERR, "config/app.php not found at {$configPath}\n");
    exit(1);
}

$content = file_get_contents($configPath);
$provider = 'App\\Plugins\\TrustVerification\\TrustVerificationServiceProvider::class';

if (str_contains($content, 'TrustVerificationServiceProvider')) {
    echo "Already registered.\n";
    exit(0);
}

$anchors = [
    'App\\Plugins\\AreaListing\\AreaListingServiceProvider::class,',
    'App\\Plugins\\NearbyPlaces\\NearbyPlacesServiceProvider::class,',
    'App\\Providers\\RouteServiceProvider::class,',
];

foreach ($anchors as $needle) {
    if (str_contains($content, $needle)) {
        $content = str_replace($needle, $provider . ",\n        " . $needle, $content);
        file_put_contents($configPath, $content);
        echo "TrustVerificationServiceProvider registered (after existing plugin provider).\n";
        echo "Run: cd /www/wwwroot/admin-homes && php artisan optimize:clear\n";
        exit(0);
    }
}

fwrite(STDERR, "Could not find anchor in config/app.php — add manually:\n  {$provider}\n");
exit(1);
