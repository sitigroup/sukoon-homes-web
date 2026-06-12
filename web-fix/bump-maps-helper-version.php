<?php

$version = '20260522pinalways';
$files = glob('/www/wwwroot/admin-homes/resources/views/property/*.blade.php')
    + glob('/www/wwwroot/admin-homes/resources/views/project/*.blade.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'maps-helper.js') === false) {
        continue;
    }
    $updated = preg_replace(
        "/(maps-helper\.js'\)\s*\}\}\?v=)[^\"]+/",
        '$1' . $version,
        $content
    );
    if ($updated !== $content) {
        file_put_contents($file, $updated);
        echo "updated {$file}\n";
    } else {
        echo "no match {$file}\n";
    }
}

passthru('cd /www/wwwroot/admin-homes && sudo -u www php artisan view:clear 2>&1');
