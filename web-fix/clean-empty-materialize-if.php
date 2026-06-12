<?php
foreach ([
    '/www/wwwroot/admin-homes/app/Http/Controllers/ProjectController.php',
    '/www/wwwroot/admin-homes/app/Http/Controllers/PropertController.php',
] as $path) {
    $c = file_get_contents($path);
    $c = preg_replace(
        '/if \(\$request->request_status == "approved"\) \{\s*if \(class_exists\(\\\\App\\\\Plugins\\\\AreaListing\\\\Services\\\\AreaListingService::class\)\) \{\s*\}\s*/',
        'if ($request->request_status == "approved") {' . "\n",
        $c
    );
    file_put_contents($path, $c);
    echo "ok $path\n";
}
