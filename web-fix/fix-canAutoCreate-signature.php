<?php
$path = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';
$c = file_get_contents($path);

$c = str_replace(
    '    public static function canAutoCreateAreasOnSave(): bool
    {
        if (request()->boolean(\'area_listing_admin_save\')) {
            return true;
        }

        // User dashboard (/user/...) — suggest only, never auto-create in master area tables
        if (request()->boolean(\'area_listing_user_portal\')) {
            return false;
        }

        return self::userCanAutoCreateAreas(self::resolveAreaListingActor());
    }',
    '    public static function canAutoCreateAreasOnSave(?Request $request = null): bool
    {
        $request = $request ?? request();

        if ($request->boolean(\'area_listing_admin_save\')) {
            return true;
        }

        if ($request->boolean(\'area_listing_user_portal\')) {
            return false;
        }

        return self::userCanAutoCreateAreas(self::resolveAreaListingActor());
    }',
    $c,
    $count
);

if (! $count) {
    echo "WARN: signature not replaced\n";
    exit(1);
}

if (! str_contains($c, 'canAutoCreateAreasOnSave($request)')) {
    $c = preg_replace(
        '/\$canAutoCreate = self::canAutoCreateAreasOnSave\(\);/',
        '$canAutoCreate = self::canAutoCreateAreasOnSave($request);',
        $c,
        1
    );
}

// Merge portal flags into merged request before save
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
        self::saveListingLocation',
        $helper . '
    public static function savePropertyLocation($property, Request $request): void
    {
        $merged = self::buildMergedPropertyAreaListingRequest($property, $request);
        $merged = self::mergeAreaListingPortalFlags($request, $merged);
        self::saveListingLocation',
        $c,
        1,
        $p
    );
    echo $p ? "OK: savePropertyLocation merge flags\n" : "WARN: savePropertyLocation\n";
}

if (str_contains($c, 'saveProjectLocation($project, Request $request): void
    {
        $merged = self::buildMergedProjectAreaListingRequest($project, $request);
        self::saveListingLocation') && ! str_contains($c, 'mergeAreaListingPortalFlags($request, $merged);
        self::saveListingLocation(\'area_listing_project_locations\'')) {
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
        $c,
        1,
        $q
    );
    echo $q ? "OK: saveProjectLocation merge flags\n" : "";
}

file_put_contents($path, $c);
echo "OK: canAutoCreateAreasOnSave uses Request param\n";
passthru('php -l ' . escapeshellarg($path));
