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

