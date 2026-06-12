<?php
/**
 * OPTIONAL — registers ThemeServiceProvider in config/app.php.
 *
 * wrteam CORE — run only after:
 *   1. Fresh backup: bash scripts/final-sukoon-complete-backup.sh
 *   2. User explicitly approved this one-line hook
 *
 *   cd /www/wwwroot/admin-homes
 *   php /path/to/cursr/web-fix/patch-theme-register.php
 */
declare(strict_types=1);

$configPath = is_file(getcwd() . '/artisan')
    ? getcwd() . '/config/app.php'
    : '/www/wwwroot/admin-homes/config/app.php';

if (! is_file($configPath)) {
    fwrite(STDERR, "config/app.php not found at {$configPath}\n");
    exit(1);
}

$content = file_get_contents($configPath);
$provider = 'App\\Plugins\\Theme\\ThemeServiceProvider::class';

if (str_contains($content, 'ThemeServiceProvider')) {
    echo "Already registered.\n";
    exit(0);
}

$anchors = [
    'App\\Plugins\\TrustVerification\\TrustVerificationServiceProvider::class,',
    'App\\Plugins\\NearbyPlaces\\NearbyPlacesServiceProvider::class,',
    'App\\Plugins\\AreaListing\\AreaListingServiceProvider::class,',
    'App\\Providers\\RouteServiceProvider::class,',
];

foreach ($anchors as $needle) {
    if (str_contains($content, $needle)) {
        $content = str_replace($needle, $provider . ",\n        " . $needle, $content);
        file_put_contents($configPath, $content);
        echo "ThemeServiceProvider registered.\n";
        echo "Run: php artisan migrate --path=app/Plugins/Theme/database/migrations\n";
        echo "Run: php artisan optimize:clear\n";
        exit(0);
    }
}

fwrite(STDERR, "Could not find anchor — add manually:\n  {$provider}\n");
exit(1);
