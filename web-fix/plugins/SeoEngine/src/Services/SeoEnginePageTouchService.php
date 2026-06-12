<?php

namespace App\Plugins\SeoEngine\Services;

use App\Plugins\SeoEngine\Models\SeoEnginePage;

class SeoEnginePageTouchService
{
    /**
     * @param  array{city_id?:int|null,area_id?:int|null,sub_area_id?:int|null}  $location
     */
    public function touchForLocation(array $location): int
    {
        $cityId = $location['city_id'] ?? null;
        $areaId = $location['area_id'] ?? null;
        $subAreaId = $location['sub_area_id'] ?? null;

        if (! $cityId && ! $areaId && ! $subAreaId) {
            return 0;
        }

        $query = SeoEnginePage::query();
        $query->where(function ($q) use ($cityId, $areaId, $subAreaId) {
            if ($cityId) {
                $q->orWhere('params->city_id', $cityId);
            }
            if ($areaId) {
                $q->orWhere('params->area_id', $areaId);
            }
            if ($subAreaId) {
                $q->orWhere('params->sub_area_id', $subAreaId);
            }
        });

        return $query->update(['updated_at' => now()]);
    }

    /**
     * @param  list<array{city_id?:int|null,area_id?:int|null,sub_area_id?:int|null}>  $locations
     */
    public function touchForLocations(array $locations): int
    {
        $touched = 0;
        foreach ($locations as $location) {
            $touched += $this->touchForLocation($location);
        }

        return $touched;
    }
}
