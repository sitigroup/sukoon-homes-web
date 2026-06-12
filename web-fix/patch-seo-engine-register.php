<?php
declare(strict_types=1);

$configPath = is_file(getcwd() . '/artisan')
    ? getcwd() . '/config/app.php'
    : '/www/wwwroot/admin-homes/config/app.php';

if (! is_file($configPath)) {
    fwrite(STDERR, "config/app.php not found at {$configPath}\n");
    exit(1);
}

$backup = $configPath . '.bak-seo-engine-' . date('Ymd-His');
copy($configPath, $backup);
echo "Backup created: {$backup}\n";

$content = file_get_contents($configPath);
$provider = 'App\\Plugins\\SeoEngine\\SeoEngineServiceProvider::class';

if (str_contains($content, 'SeoEngineServiceProvider')) {
    echo "Already registered.\n";
    exit(0);
}

$anchors = [
    'App\\Plugins\\OwnerDashboard\\OwnerDashboardServiceProvider::class,',
    'App\\Plugins\\Whatsapp\\WhatsappServiceProvider::class,',
    'App\\Providers\\RouteServiceProvider::class,',
];

foreach ($anchors as $needle) {
    if (str_contains($content, $needle)) {
        $content = str_replace($needle, $provider . ",\n        " . $needle, $content);
        file_put_contents($configPath, $content);
        echo "SeoEngineServiceProvider registered.\n";
        echo "Run: php artisan migrate --path=app/Plugins/SeoEngine/database/migrations --force\n";
        echo "Run: php artisan optimize:clear\n";
        exit(0);
    }
}

fwrite(STDERR, "Could not find anchor — add manually:\n  {$provider}\n");
exit(1);
