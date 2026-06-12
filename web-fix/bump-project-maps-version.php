<?php
$version = '20260522pinalways';
$files = [
    '/www/wwwroot/admin-homes/resources/views/project/create.blade.php',
    '/www/wwwroot/admin-homes/resources/views/project/edit.blade.php',
];
foreach ($files as $file) {
    $content = file_get_contents($file);
    $updated = preg_replace(
        "/maps-helper\.js'\)\s*\}\}\?v=[^\"']+/",
        "maps-helper.js') }}?v={$version}",
        $content
    );
    if ($updated !== $content) {
        file_put_contents($file, $updated);
        echo "bumped {$file}\n";
    } else {
        echo "no change {$file}\n";
    }
}
passthru('cd /www/wwwroot/admin-homes && sudo -u www php artisan view:clear 2>&1');
