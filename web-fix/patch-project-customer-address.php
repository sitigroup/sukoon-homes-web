<?php

$version = '20260522pinalways';
$files = [
    '/www/wwwroot/admin-homes/resources/views/project/create.blade.php',
    '/www/wwwroot/admin-homes/resources/views/project/edit.blade.php',
];

$create = file_get_contents(__DIR__ . '/project-create-server.blade.php');
$edit = file_get_contents(__DIR__ . '/project-edit-server.blade.php');

file_put_contents($files[0], $create);
file_put_contents($files[1], $edit);
echo "deployed create + edit\n";

foreach ($files as $file) {
    $content = file_get_contents($file);
    $updated = preg_replace(
        "/(maps-helper\.js'\)\s*\}\}\?v=)[^\"]+/",
        '$1' . $version,
        $content
    );
    if ($updated !== $content) {
        file_put_contents($file, $updated);
        echo "version {$file}\n";
    }
}

passthru('cd /www/wwwroot/admin-homes && sudo -u www php artisan view:clear 2>&1');
