<?php
/**
 * Minimal wrteam route/view hooks — same pattern as AreaListing/NearbyPlaces on Sukoon server.
 * Run after backup: php patch-trust-verification-routes.php
 */
declare(strict_types=1);

$admin = is_file(getcwd() . '/artisan') ? getcwd() : '/www/wwwroot/admin-homes';
$api = $admin . '/routes/api.php';
$web = $admin . '/routes/web.php';
$appSp = $admin . '/app/Providers/AppServiceProvider.php';

$apiBlock = <<<'PHP'

if (file_exists(app_path('Plugins/TrustVerification/routes/api.php'))) {
    require app_path('Plugins/TrustVerification/routes/api.php');
}

PHP;

$webBlock = <<<'PHP'

if (file_exists(app_path('Plugins/TrustVerification/routes/web.php'))) {
    require app_path('Plugins/TrustVerification/routes/web.php');
}

PHP;

$viewLine = "        \$this->loadViewsFrom(app_path('Plugins/TrustVerification/views'), 'trust-verification');";
$observerLine = "        \\App\\Models\\PaymentTransaction::observe(\\App\\Plugins\\TrustVerification\\Observers\\PaymentTransactionObserver::class);";
$providerLine = '        App\\Plugins\\TrustVerification\\TrustVerificationServiceProvider::class,';

foreach ([$api => $apiBlock, $web => $webBlock] as $file => $block) {
    $c = file_get_contents($file);
    if (str_contains($c, 'Plugins/TrustVerification/routes/')) {
        echo basename($file) . ": already patched\n";
    } else {
        file_put_contents($file, rtrim($c) . "\n" . $block);
        echo basename($file) . ": patched\n";
    }
}

$sp = file_get_contents($appSp);
if (str_contains($sp, 'Plugins/TrustVerification/views')) {
    echo "AppServiceProvider: views already registered\n";
} elseif (preg_match("/loadViewsFrom\(app_path\('Plugins\/NearbyPlaces\/views'\)/", $sp, $m, PREG_OFFSET_CAPTURE)) {
    $pos = $m[0][1];
    $lineEnd = strpos($sp, "\n", $pos);
    $sp = substr($sp, 0, $lineEnd + 1) . $viewLine . "\n" . substr($sp, $lineEnd + 1);
    file_put_contents($appSp, $sp);
    echo "AppServiceProvider: trust-verification views registered\n";
} else {
    fwrite(STDERR, "AppServiceProvider: add manually:\n  {$viewLine}\n");
    exit(1);
}

$sp = file_get_contents($appSp);
if (str_contains($sp, 'PaymentTransactionObserver')) {
    echo "AppServiceProvider: payment observer already registered\n";
} elseif (str_contains($sp, 'Plugins/TrustVerification/views')) {
    $sp = str_replace(
        $viewLine,
        $viewLine . "\n" . $observerLine,
        $sp
    );
    file_put_contents($appSp, $sp);
    echo "AppServiceProvider: payment observer registered\n";
} else {
    fwrite(STDERR, "AppServiceProvider: add manually:\n  {$observerLine}\n");
}

$configPath = $admin . '/config/app.php';
if (is_file($configPath)) {
    $cfg = file_get_contents($configPath);
    if (str_contains($cfg, 'TrustVerificationServiceProvider')) {
        echo "config/app.php: TrustVerificationServiceProvider already registered\n";
    } else {
        $anchors = [
            'App\\Plugins\\AreaListing\\AreaListingServiceProvider::class,',
            'App\\Plugins\\NearbyPlaces\\NearbyPlacesServiceProvider::class,',
            'App\\Providers\\RouteServiceProvider::class,',
        ];
        $registered = false;
        foreach ($anchors as $needle) {
            if (str_contains($cfg, $needle)) {
                $cfg = str_replace($needle, $providerLine . "\n        " . $needle, $cfg);
                file_put_contents($configPath, $cfg);
                echo "config/app.php: TrustVerificationServiceProvider registered\n";
                $registered = true;
                break;
            }
        }
        if (! $registered) {
            fwrite(STDERR, "config/app.php: add TrustVerificationServiceProvider manually\n");
        }
    }
}

echo "Done.\n";
