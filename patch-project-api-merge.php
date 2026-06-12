<?php

$path = '/www/wwwroot/admin-homes/app/Http/Controllers/Api/ProjectApiController.php';
$text = file_get_contents($path);
$old = <<<'PHP'
            if (class_exists(\App\Plugins\AreaListing\Services\AreaListingService::class)) {
                \App\Plugins\AreaListing\Services\AreaListingService::saveProjectLocation($project, $request);
            }
PHP;
$new = <<<'PHP'
            if (class_exists(\App\Plugins\AreaListing\Services\AreaListingService::class)) {
                if (! empty($request->id)) {
                    $areaListingRequest = \App\Plugins\AreaListing\Services\AreaListingService::buildMergedProjectAreaListingRequest($project, $request);
                    \App\Plugins\AreaListing\Services\AreaListingService::saveProjectLocation($project, $areaListingRequest);
                } else {
                    \App\Plugins\AreaListing\Services\AreaListingService::saveProjectLocation($project, $request);
                }
            }
PHP;

if (strpos($text, 'buildMergedProjectAreaListingRequest') !== false) {
    echo "Already patched\n";
    exit(0);
}
if (strpos($text, $old) === false) {
    fwrite(STDERR, "Pattern not found\n");
    exit(1);
}
file_put_contents($path, str_replace($old, $new, $text));
echo "ProjectApiController patched\n";
