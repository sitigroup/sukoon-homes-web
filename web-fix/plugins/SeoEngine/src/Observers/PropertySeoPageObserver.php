<?php

namespace App\Plugins\SeoEngine\Observers;

use App\Models\Property;
use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Services\SeoEngineIndexNowService;
use Illuminate\Support\Facades\DB;

class PropertySeoPageObserver
{
    public function __construct(private SeoEngineIndexNowService $indexNow)
    {
    }

    public function saved(Property $property): void
    {
        if ((int) $property->status !== 1 || $property->request_status !== 'approved') {
            return;
        }

        $loc = DB::table('area_listing_property_locations')->where('property_id', $property->id)->first();
        if (! $loc) {
            return;
        }

        $paths = SeoEnginePage::query()
            ->where('is_indexable', true)
            ->where(function ($q) use ($loc) {
                $q->where('params->city_id', $loc->city_id)
                    ->orWhere('params->area_id', $loc->area_id);
                if ($loc->sub_area_id) {
                    $q->orWhere('params->sub_area_id', $loc->sub_area_id);
                }
            })
            ->pluck('path');

        if ($paths->isEmpty()) {
            return;
        }

        SeoEnginePage::query()->whereIn('path', $paths)->update(['updated_at' => now()]);

        $web = rtrim((string) config('app.web_url', 'https://homes.sukoon.group'), '/');
        if ($property->slug_id) {
            $this->indexNow->pingUrl("{$web}/property-details/{$property->slug_id}/");
        }
    }
}
