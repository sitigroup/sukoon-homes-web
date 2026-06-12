<?php
declare(strict_types=1);

$configPath = is_file(getcwd() . '/artisan')
    ? getcwd() . '/config/app.php'
    : '/www/wwwroot/admin-homes/config/app.php';

if (! is_file($configPath)) {
    fwrite(STDERR, "config/app.php not found at {$configPath}\n");
    exit(1);
}

$backup = $configPath . '.bak-' . date('Ymd-His');
copy($configPath, $backup);
echo "Backup created: {$backup}\n";

$content = file_get_contents($configPath);
$provider = 'App\\Plugins\\Whatsapp\\WhatsappServiceProvider::class';

if (str_contains($content, 'WhatsappServiceProvider')) {
    echo "Already registered.\n";
    exit(0);
}

$anchors = [
    'App\\Plugins\\TrustVerification\\TrustVerificationServiceProvider::class,',
    'App\\Plugins\\AreaListing\\AreaListingServiceProvider::class,',
    'App\\Providers\\RouteServiceProvider::class,',
];

foreach ($anchors as $needle) {
    if (str_contains($content, $needle)) {
        $content = str_replace($needle, $provider . ",\n        " . $needle, $content);
        file_put_contents($configPath, $content);
        echo "WhatsappServiceProvider registered.\n";
        echo "Run: php artisan migrate --force\n";
        echo "Run: php artisan optimize:clear\n";
        exit(0);
    }
}

fwrite(STDERR, "Could not find anchor — add manually:\n  {$provider}\n");
exit(1);

