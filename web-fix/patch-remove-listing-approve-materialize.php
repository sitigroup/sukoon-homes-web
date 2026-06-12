<?php
$hooks = [
    '/www/wwwroot/admin-homes/app/Http/Controllers/ProjectController.php' => [
        "                    if (class_exists(\\App\\Plugins\\AreaListing\\Services\\AreaListingService::class)) {\n                        \\App\\Plugins\\AreaListing\\Services\\AreaListingService::materializeLocationOnListingApproval((int) \$request->id, 'project');\n                    }\n" => '',
    ],
    '/www/wwwroot/admin-homes/app/Http/Controllers/PropertController.php' => [
        "                    if (class_exists(\\App\\Plugins\\AreaListing\\Services\\AreaListingService::class)) {\n                        \\App\\Plugins\\AreaListing\\Services\\AreaListingService::materializeLocationOnListingApproval((int) \$request->id, 'property');\n                    }\n" => '',
    ],
];

foreach ($hooks as $path => $replacements) {
    $c = file_get_contents($path);
    foreach ($replacements as $from => $to) {
        if (str_contains($c, $from)) {
            $c = str_replace($from, $to, $c);
            echo "removed hook in $path\n";
        } elseif (str_contains($c, 'materializeLocationOnListingApproval')) {
            echo "WARN: hook variant still in $path\n";
        } else {
            echo "already clean $path\n";
        }
    }
    file_put_contents($path, $c);
}

copy(__DIR__ . '/AreaListingService.php', '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php');
echo "service deployed\n";
passthru('cd /www/wwwroot/admin-homes && php -l app/Plugins/AreaListing/Services/AreaListingService.php 2>&1');
passthru('cd /www/wwwroot/admin-homes && sudo -u www php artisan optimize:clear 2>&1 | tail -2');
