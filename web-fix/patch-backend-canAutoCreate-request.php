<?php
$path = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';
$c = file_get_contents($path);

$old = <<<'PHP'
    public static function canAutoCreateAreasOnSave(): bool
    {
        if (request()->boolean('area_listing_admin_save')) {
            return true;
        }

        // User dashboard (/user/...) — suggest only, never auto-create in master area tables
        if (request()->boolean('area_listing_user_portal')) {
            return false;
        }

        return self::userCanAutoCreateAreas(self::resolveAreaListingActor());
    }
PHP;

$new = <<<'PHP'
    public static function canAutoCreateAreasOnSave(?Request $request = null): bool
    {
        $request = $request ?? request();

        if ($request->boolean('area_listing_admin_save')) {
            return true;
        }

        // User dashboard (/user/...) — suggest only, never auto-create in master area tables
        if ($request->boolean('area_listing_user_portal')) {
            return false;
        }

        return self::userCanAutoCreateAreas(self::resolveAreaListingActor());
    }
PHP;

if (str_contains($c, 'canAutoCreateAreasOnSave(?Request $request')) {
    echo "SKIP: canAutoCreateAreasOnSave already accepts Request\n";
} elseif (str_contains($c, $old)) {
    $c = str_replace($old, $new, $c);
    echo "OK: canAutoCreateAreasOnSave(Request)\n";
} else {
    echo "WARN: canAutoCreateAreasOnSave block not found\n";
}

$c = preg_replace(
    '/\$canAutoCreate = self::canAutoCreateAreasOnSave\(\);/',
    '$canAutoCreate = self::canAutoCreateAreasOnSave($request);',
    $c,
    1,
    $count
);
echo $count ? "OK: saveListingLocation uses \$request\n" : "WARN: saveListingLocation line not updated\n";

file_put_contents($path, $c);
