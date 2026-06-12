<?php

$base = '/www/wwwroot/admin-homes';

$files = [
    'Handler' => $base . '/app/Exceptions/Handler.php',
    'api' => $base . '/app/Plugins/AreaListing/routes/api.php',
    'csrf' => $base . '/app/Http/Middleware/VerifyCsrfToken.php',
];

$sources = [
    'Handler' => __DIR__ . '/Handler.php',
    'api' => __DIR__ . '/area-listing-api-routes.php',
    'csrf' => __DIR__ . '/VerifyCsrfToken.php',
];

foreach ($files as $key => $dest) {
    $src = $sources[$key];
    if (! is_readable($src)) {
        fwrite(STDERR, "missing source: {$src}\n");
        exit(1);
    }
    if (! copy($src, $dest)) {
        fwrite(STDERR, "copy failed: {$dest}\n");
        exit(1);
    }
    echo "deployed {$key} -> {$dest}\n";
}

passthru('cd ' . escapeshellarg($base) . ' && php artisan optimize:clear 2>&1', $code);
exit($code);
