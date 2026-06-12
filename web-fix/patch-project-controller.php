<?php
$path = '/www/wwwroot/admin-homes/app/Http/Controllers/ProjectController.php';
$c = file_get_contents($path);
$patterns = [
    [
        '$areaListingRequest = \App\Plugins\AreaListing\Services\AreaListingService::buildMergedProjectAreaListingRequest($project, $request);
                \App\Plugins\AreaListing\Services\AreaListingService::saveProjectLocation($project, $areaListingRequest);',
        '\App\Plugins\AreaListing\Services\AreaListingService::saveProjectLocation($project, $request);',
    ],
];
$changed = false;
foreach ($patterns as [$old, $new]) {
    if (str_contains($c, $old)) {
        $c = str_replace($old, $new, $c);
        $changed = true;
    }
}
if ($changed) {
    file_put_contents($path, $c);
    echo "OK ProjectController\n";
} else {
    echo "SKIP ProjectController\n";
}
