<?php

namespace App\Plugins\SeoEngine\Observers;

use App\Models\Property;
use App\Plugins\SeoEngine\Services\SeoEngineIndexNowService;
use App\Plugins\SeoEngine\Services\SeoEnginePageTouchService;
use Illuminate\Support\Facades\DB;

class PropertySeoPageObserver
{
    /** @var array<int, array{city_id:?int,area_id:?int,sub_area_id:?int}> */
    private static array $locationBeforeSave = [];

    public function __construct(
        private SeoEngineIndexNowService $indexNow,
        private SeoEnginePageTouchService $pageTouch
    ) {
    }

    public function updating(Property $property): void
    {
        $row = DB::table('area_listing_property_locations')
            ->where('property_id', $property->id)
            ->first(['city_id', 'area_id', 'sub_area_id']);

        if ($row) {
            self::$locationBeforeSave[$property->id] = [
                'city_id' => $row->city_id ? (int) $row->city_id : null,
                'area_id' => $row->area_id ? (int) $row->area_id : null,
                'sub_area_id' => $row->sub_area_id ? (int) $row->sub_area_id : null,
            ];
        }
    }

    public function saved(Property $property): void
    {
        $loc = DB::table('area_listing_property_locations')
            ->where('property_id', $property->id)
            ->first(['city_id', 'area_id', 'sub_area_id']);

        $locations = [];
        $before = self::$locationBeforeSave[$property->id] ?? null;
        unset(self::$locationBeforeSave[$property->id]);

        if ($before) {
            $locations[] = $before;
        }
        if ($loc) {
            $locations[] = [
                'city_id' => $loc->city_id ? (int) $loc->city_id : null,
                'area_id' => $loc->area_id ? (int) $loc->area_id : null,
                'sub_area_id' => $loc->sub_area_id ? (int) $loc->sub_area_id : null,
            ];
        }

        if ($locations !== []) {
            $this->pageTouch->touchForLocations($locations);
        }

        if ((int) $property->status !== 1 || $property->request_status !== 'approved') {
            return;
        }

        $web = rtrim((string) config('app.web_url', 'https://homes.sukoon.group'), '/');
        if ($property->slug_id) {
            $this->indexNow->pingUrl("{$web}/property-details/{$property->slug_id}/");
        }
    }
}
