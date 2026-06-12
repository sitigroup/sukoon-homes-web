<?php
$base = '/www/wwwroot/admin-homes';
$files = [
    $base . '/resources/views/property/edit.blade.php',
    $base . '/resources/views/property/create.blade.php',
];
$version = '20260521autocreate';
foreach ($files as $path) {
    if (!is_file($path)) {
        echo "missing: $path\n";
        continue;
    }
    $content = file_get_contents($path);
    $updated = preg_replace(
        "/maps-helper\\.js'\\)\\s*\\}\\}\\?v=[^\"]+/",
        "maps-helper.js') }}?v={$version}",
        $content,
        -1,
        $count
    );
    if ($count < 1) {
        $updated = preg_replace(
            "/maps-helper\\.js'\\)\\s*\\}\\}/",
            "maps-helper.js') }}?v={$version}",
            $content,
            1,
            $count2
        );
        $count = $count2;
    }
    file_put_contents($path, $updated);
    echo basename($path) . " updated={$count}\n";
}
passthru('cd ' . escapeshellarg($base) . ' && php artisan view:clear 2>&1');
