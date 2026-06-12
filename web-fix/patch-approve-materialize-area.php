<?php
/**
 * On project/property approval: create area/sub-area from listing names and close pending suggestions.
 */
$servicePath = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';
$localService = __DIR__ . '/AreaListingService.php';

if (!is_file($localService)) {
    fwrite(STDERR, "missing local AreaListingService.php\n");
    exit(1);
}

$server = file_get_contents($servicePath);
$local = file_get_contents($localService);

$marker = 'public static function materializeLocationOnListingApproval';
if (!str_contains($server, $marker)) {
    $needle = "    public static function saveProjectLocation(\$project, Request \$request): void\n    {\n        \$merged = self::buildMergedProjectAreaListingRequest(\$project, \$request);\n        \$merged = self::mergeAreaListingPortalFlags(\$request, \$merged);\n        self::saveListingLocation('area_listing_project_locations', 'project_id', \$project->id, \$merged);\n    }\n\n    public static function applyPropertyFilters";
    $insert = file_get_contents($localService);
    if (preg_match('/public static function materializeLocationOnListingApproval.*?private static function markPendingSuggestionsResolvedForLocation.*?}\n\n    public static function applyPropertyFilters/s', $insert, $m)) {
        $block = $m[0];
        $block = str_replace('    public static function applyPropertyFilters', '', $block);
        if (str_contains($server, $needle)) {
            $server = str_replace($needle, "    public static function saveProjectLocation(\$project, Request \$request): void\n    {\n        \$merged = self::buildMergedProjectAreaListingRequest(\$project, \$request);\n        \$merged = self::mergeAreaListingPortalFlags(\$request, \$merged);\n        self::saveListingLocation('area_listing_project_locations', 'project_id', \$project->id, \$merged);\n    }\n\n" . $block . "\n    public static function applyPropertyFilters", $server);
            file_put_contents($servicePath, $server);
            echo "inserted materialize methods\n";
        } else {
            copy($localService, $servicePath);
            echo "replaced full AreaListingService.php\n";
        }
    } else {
        copy($localService, $servicePath);
        echo "replaced full AreaListingService.php (fallback)\n";
    }
} else {
    echo "materialize methods already present\n";
}

$hooks = [
    [
        '/www/wwwroot/admin-homes/app/Http/Controllers/ProjectController.php',
        'if ($request->request_status == "approved") {',
        'if ($request->request_status == "approved") {
                    if (class_exists(\App\Plugins\AreaListing\Services\AreaListingService::class)) {
                        \App\Plugins\AreaListing\Services\AreaListingService::materializeLocationOnListingApproval((int) $request->id, \'project\');
                    }',
        'materializeLocationOnListingApproval((int) $request->id, \'project\')',
    ],
    [
        '/www/wwwroot/admin-homes/app/Http/Controllers/PropertController.php',
        'if ($request->request_status == "approved") {',
        'if ($request->request_status == "approved") {
                    if (class_exists(\App\Plugins\AreaListing\Services\AreaListingService::class)) {
                        \App\Plugins\AreaListing\Services\AreaListingService::materializeLocationOnListingApproval((int) $request->id, \'property\');
                    }',
        'materializeLocationOnListingApproval((int) $request->id, \'property\')',
    ],
];

foreach ($hooks as [$path, $search, $replace, $check]) {
    if (!is_file($path)) {
        echo "skip missing $path\n";
        continue;
    }
    $c = file_get_contents($path);
    if (str_contains($c, $check)) {
        echo "hook ok $path\n";
        continue;
    }
    if (str_contains($c, $search)) {
        $c = str_replace($search, $replace, $c);
        file_put_contents($path, $c);
        echo "hooked $path\n";
    } else {
        echo "hook pattern not found $path\n";
    }
}

passthru('cd /www/wwwroot/admin-homes && php -l app/Plugins/AreaListing/Services/AreaListingService.php 2>&1');
passthru('cd /www/wwwroot/admin-homes && sudo -u www php artisan optimize:clear 2>&1 | tail -3');
