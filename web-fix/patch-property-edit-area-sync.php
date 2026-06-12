<?php

$base = '/www/wwwroot/admin-homes';
$files = [
    'listing' => $base . '/app/Plugins/AreaListing/views/admin/partials/listing-location-fields.blade.php',
    'maps' => $base . '/public/assets/js/maps-helper.js',
];

$localBase = dirname(__DIR__) . '/web-fix';
$sources = [
    'listing' => $localBase . '/listing-location-fields.blade.php',
    'maps' => $localBase . '/maps-helper.js',
];

foreach ($files as $key => $dest) {
    $src = $sources[$key];
    if (!is_file($src)) {
        echo "FAIL: missing local {$src}\n";
        exit(1);
    }
    if (!copy($src, $dest)) {
        echo "FAIL: could not copy to {$dest}\n";
        exit(1);
    }
    echo "OK: deployed {$key}\n";
}

$grep = shell_exec("grep -rl 'maps-helper.js' {$base}/resources/views {$base}/app/Plugins 2>/dev/null | head -20");
$paths = array_filter(array_map('trim', explode("\n", (string) $grep)));
$updated = 0;
foreach ($paths as $path) {
    $content = file_get_contents($path);
    $new = preg_replace('/maps-helper\.js\?v=[0-9a-z]+/i', 'maps-helper.js?v=20260521area', $content, -1, $count);
    if ($count) {
        file_put_contents($path, $new);
        $updated++;
    }
}
echo "OK: cache bust on {$updated} blade(s)\n";
echo "DONE\n";
