<?php
$path = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';
$c = file_get_contents($path);

$savePropertyOld = <<<'PHP'
    public static function savePropertyLocation($property, Request $request): void
    {
        $merged = self::buildMergedPropertyAreaListingRequest($property, $request);
        self::saveListingLocation('area_listing_property_locations', 'property_id', $property->id, $merged);
    }
PHP;

$savePropertyNew = <<<'PHP'
    public static function savePropertyLocation($property, Request $request): void
    {
        $merged = self::buildMergedPropertyAreaListingRequest($property, $request);
        $merged = self::mergeAreaListingPortalFlags($request, $merged);
        self::saveListingLocation('area_listing_property_locations', 'property_id', $property->id, $merged);
    }
PHP;

$saveProjectOld = <<<'PHP'
    public static function saveProjectLocation($project, Request $request): void
    {
        $merged = self::buildMergedProjectAreaListingRequest($project, $request);
        self::saveListingLocation('area_listing_project_locations', 'project_id', $project->id, $merged);
    }
PHP;

$saveProjectNew = <<<'PHP
    public static function saveProjectLocation($project, Request $request): void
    {
        $merged = self::buildMergedProjectAreaListingRequest($project, $request);
        $merged = self::mergeAreaListingPortalFlags($request, $merged);
        self::saveListingLocation('area_listing_project_locations', 'project_id', $project->id, $merged);
    }
PHP;

if (! str_contains($c, 'mergeAreaListingPortalFlags')) {
    $insertBefore = '    public static function savePropertyLocation($property, Request $request): void';
    $helper = <<<'PHP'

    /** Carry user-portal / admin-save flags into merged request used by saveListingLocation. */
    private static function mergeAreaListingPortalFlags(Request $source, Request $merged): Request
    {
        $extra = [];
        if ($source->boolean('area_listing_user_portal')) {
            $extra['area_listing_user_portal'] = 1;
        }
        if ($source->boolean('area_listing_admin_save')) {
            $extra['area_listing_admin_save'] = 1;
        }
        if ($extra === []) {
            return $merged;
        }

        return Request::create('/', 'POST', array_merge($merged->all(), $extra));
    }

PHP;
    $c = str_replace($insertBefore, $helper . $insertBefore, $c);
    echo "OK: mergeAreaListingPortalFlags helper\n";
}

if (str_contains($c, $savePropertyOld)) {
    $c = str_replace($savePropertyOld, $savePropertyNew, $c);
    echo "OK: savePropertyLocation\n";
}

if (str_contains($c, $saveProjectOld)) {
    $c = str_replace($saveProjectOld, $saveProjectNew, $c);
    echo "OK: saveProjectLocation\n";
}

file_put_contents($path, $c);
echo "DONE AreaListingService\n";
