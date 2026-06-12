<?php

$fix = "    <script src=\"{{ asset('assets/js/maps-helper.js') }}?v=20260522pinalways\"></script>\n";

$files = [
    '/www/wwwroot/admin-homes/resources/views/property/create.blade.php',
    '/www/wwwroot/admin-homes/resources/views/property/edit.blade.php',
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    $content = preg_replace(
        '/<script src="\{\{ asset\(\'assets\/js\/[^"]*"><\/script>/',
        trim($fix),
        $content,
        1,
        $count
    );
    if ($count) {
        file_put_contents($file, $content);
        echo "fixed {$file}\n";
    } else {
        echo "no broken tag in {$file}\n";
    }
}

passthru('cd /www/wwwroot/admin-homes && sudo -u www php artisan view:clear 2>&1');
