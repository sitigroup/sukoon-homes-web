<?php
$p = '/www/wwwroot/admin-homes/app/Http/Controllers/ProjectController.php';
$c = file_get_contents($p);
$needle = 'buildMergedProjectAreaListingRequest($project, $request)';
if (strpos($c, $needle) === false) {
    echo "SKIP\n";
    exit(0);
}
$c = preg_replace(
    '/\s*\$areaListingRequest = \\\\App\\\\Plugins\\\\AreaListing\\\\Services\\\\AreaListingService::buildMergedProjectAreaListingRequest\(\$project, \$request\);\s*/',
    "\n",
    $c,
    1
);
$c = str_replace(
    'saveProjectLocation($project, $areaListingRequest)',
    'saveProjectLocation($project, $request)',
    $c
);
file_put_contents($p, $c);
echo "OK\n";
