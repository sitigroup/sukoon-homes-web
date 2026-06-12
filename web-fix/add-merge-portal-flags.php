<?php
$p = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';
$c = file_get_contents($p);

if (str_contains($c, 'mergeAreaListingPortalFlags')) {
    echo "SKIP: already present\n";
    exit(0);
}

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

$c = str_replace($helper . '    public static function savePropertyLocation', '    public static function savePropertyLocation', $c);
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

file_put_contents($p, $c);
echo "OK\n";
passthru('php -l ' . escapeshellarg($p));
