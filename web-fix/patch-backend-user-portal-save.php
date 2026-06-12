<?php
$path = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';
$c = file_get_contents($path);

$old = <<<'PHP'
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

$new = <<<'PHP'
    public static function canAutoCreateAreasOnSave(?Request $request = null): bool
    {
        $http = request();
        $request = $request ?? $http;

        if ($http->boolean('area_listing_admin_save') || $request->boolean('area_listing_admin_save')) {
            return true;
        }

        if ($http->boolean('area_listing_user_portal') || $request->boolean('area_listing_user_portal')) {
            return false;
        }

        return self::userCanAutoCreateAreas(self::resolveAreaListingActor());
    }
PHP;

if (str_contains($c, '$http->boolean(\'area_listing_user_portal\')')) {
    echo "SKIP: canAutoCreate already dual-check\n";
} elseif (str_contains($c, $old)) {
    $c = str_replace($old, $new, $c);
    echo "OK: canAutoCreate dual-check\n";
} else {
    echo "WARN: canAutoCreate block not found\n";
}

if (! str_contains($c, 'mergeAreaListingPortalFlags')) {
    $helper = '
    private static function mergeAreaListingPortalFlags(Request $source, Request $merged): Request
    {
        $extra = [];
        if ($source->boolean(\'area_listing_user_portal\')) {
            $extra[\'area_listing_user_portal\'] = 1;
        }
        if ($source->boolean(\'area_listing_admin_save\')) {
            $extra[\'area_listing_admin_save\'] = 1;
        }
        if ($extra === []) {
            return $merged;
        }

        return Request::create(\'/\', \'POST\', array_merge($merged->all(), $extra));
    }
';
    $c = str_replace(
        '    public static function savePropertyLocation($property, Request $request): void
    {
        $merged = self::buildMergedPropertyAreaListingRequest($property, $request);
        self::saveListingLocation(\'area_listing_property_locations\'',
        $helper . '    public static function savePropertyLocation($property, Request $request): void
    {
        $merged = self::buildMergedPropertyAreaListingRequest($property, $request);
        $merged = self::mergeAreaListingPortalFlags($request, $merged);
        self::saveListingLocation(\'area_listing_property_locations\'',
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
} else {
    echo "SKIP: merge helper exists\n";
}

file_put_contents($path, $c);
passthru('php -l ' . escapeshellarg($path));
