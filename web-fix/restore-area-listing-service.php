<?php
$path = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';
$c = file_get_contents($path);

// Undo broken sed
$c = str_replace('\->boolean', 'request()->boolean', $c);
$c = preg_replace('/public static function canAutoCreateAreasOnSave\(\?Request\s*=\s*null\): bool\s*\n\s*=\s*\?\?\s*request\(\);\s*\n/', '', $c);

$good = <<<'PHP'
    public static function canAutoCreateAreasOnSave(?Request $request = null): bool
    {
        $request = $request ?? request();

        if ($request->boolean('area_listing_admin_save')) {
            return true;
        }

        if ($request->boolean('area_listing_user_portal')) {
            return false;
        }

        return self::userCanAutoCreateAreas(self::resolveAreaListingActor());
    }

PHP;

if (preg_match('/public static function canAutoCreateAreasOnSave.*?public static function userCanAutoCreateAreas/s', $c)) {
    $c = preg_replace(
        '/public static function canAutoCreateAreasOnSave.*?(\n    \/\*\*\n     \* Admin users)/s',
        $good . '$1',
        $c,
        1
    );
    echo "OK: restored canAutoCreateAreasOnSave\n";
} else {
    echo "FAIL: could not match canAutoCreate block\n";
    exit(1);
}

if (! str_contains($c, 'mergeAreaListingPortalFlags')) {
    $helper = <<<'PHP'

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
    $c = str_replace(
        '    public static function savePropertyLocation($property, Request $request): void
    {
        $merged = self::buildMergedPropertyAreaListingRequest($property, $request);
        self::saveListingLocation',
        $helper . '    public static function savePropertyLocation($property, Request $request): void
    {
        $merged = self::buildMergedPropertyAreaListingRequest($property, $request);
        $merged = self::mergeAreaListingPortalFlags($request, $merged);
        self::saveListingLocation',
        $c
    );
    $c = str_replace(
        '    public static function saveProjectLocation($project, Request $request): void
    {
        $merged = self::buildMergedProjectAreaListingRequest($project, $request);
        self::saveListingLocation(\'area_listing_project_locations\'',
        '    public static function saveProjectLocation($project, Request $request): void
    {
        $merged = self::buildMergedProjectAreaListingRequest($project, $request);
        $merged = self::mergeAreaListingPortalFlags($request, $merged);
        self::saveListingLocation(\'area_listing_project_locations\'',
        $c
    );
    echo "OK: mergeAreaListingPortalFlags\n";
}

if (! preg_match('/\$canAutoCreate = self::canAutoCreateAreasOnSave\(\$request\);/', $c)) {
    $c = preg_replace(
        '/\$canAutoCreate = self::canAutoCreateAreasOnSave\(\);/',
        '$canAutoCreate = self::canAutoCreateAreasOnSave($request);',
        $c,
        1
    );
}

file_put_contents($path, $c);
passthru('php -l ' . escapeshellarg($path));
