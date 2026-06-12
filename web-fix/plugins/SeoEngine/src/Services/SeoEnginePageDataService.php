<?php

namespace App\Plugins\SeoEngine\Services;

use App\Models\Property;
use App\Plugins\AreaListing\Models\Area;
use App\Plugins\AreaListing\Models\City;
use App\Plugins\AreaListing\Models\SubArea;
use App\Plugins\SeoEngine\Models\SeoEngineLocalityStat;
use App\Plugins\SeoEngine\Models\SeoEnginePage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SeoEnginePageDataService
{
    public const CACHE_PREFIX = 'seo_engine:page:';

    public const CACHE_TTL_SECONDS = 1800;

    private const BEDROOM_PARAM_ID = 1;

    public function __construct(
        private SeoEnginePageGeneratorService $generator,
        private SeoEngineSettingsService $settings
    ) {
    }

    public function getByPath(string $path): ?array
    {
        $path = $this->normalizePath($path);
        $version = (int) Cache::get('seo_engine:page_cache_version', 1);

        return Cache::remember(
            self::CACHE_PREFIX . $version . ':' . md5($path),
            self::CACHE_TTL_SECONDS,
            fn () => $this->buildPayload($path)
        );
    }

    public function clearPageCache(?string $path = null): void
    {
        if ($path !== null) {
            $version = (int) Cache::get('seo_engine:page_cache_version', 1);
            Cache::forget(self::CACHE_PREFIX . $version . ':' . md5($this->normalizePath($path)));

            return;
        }
    }

    public function bustAllPageCaches(): void
    {
        Cache::increment('seo_engine:page_cache_version');
    }

    public function clearAllPageCaches(): void
    {
        $this->bustAllPageCaches();
    }

    private function buildPayload(string $path): ?array
    {
        $page = SeoEnginePage::query()->where('path', $path)->first();
        if (! $page) {
            return null;
        }

        $filters = is_array($page->params) ? $page->params : [];
        $listings = $this->fetchListings($filters, 12);
        $siblings = $this->linkSet($page, 'siblings', 12);
        $children = $this->linkSet($page, 'children', 12);
        $budgetLinks = $this->linkSet($page, 'budget', 12);
        $locality = $this->localityStats($filters);
        $topSibling = $siblings[0] ?? null;

        return [
            'page' => $this->pageRow($page),
            'listings' => $listings,
            'breadcrumbs' => $this->breadcrumbs($path),
            'links' => [
                'siblings' => $siblings,
                'children' => $children,
                'budget' => $budgetLinks,
            ],
            'locality_stats' => $locality,
            'nearby_places' => $this->nearbyPlaces($filters),
            'comparison' => $this->comparison($page, $topSibling),
        ];
    }

    private function pageRow(SeoEnginePage $page): array
    {
        return [
            'path' => $page->path,
            'page_type' => $page->page_type,
            'title' => $page->title,
            'h1' => $page->h1,
            'meta_description' => $page->meta_description,
            'intro_html' => $page->intro_html,
            'faq_json' => $page->faq_json,
            'listing_count' => $page->listing_count,
            'quality_score' => $page->quality_score,
            'is_indexable' => $page->is_indexable,
            'lock_content' => $page->lock_content,
            'content_generated_at' => optional($page->content_generated_at)->toIso8601String(),
            'updated_at' => optional($page->updated_at)->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function fetchListings(array $filters, int $limit): array
    {
        if (! class_exists(Property::class)) {
            return [];
        }

        $q = Property::query()
            ->select([
                'propertys.id',
                'propertys.slug_id',
                'propertys.title',
                'propertys.price',
                'propertys.propery_type',
                'propertys.rentduration',
                'propertys.city',
                'propertys.state',
                'propertys.updated_at',
            ])
            ->join('area_listing_property_locations as apl', 'apl.property_id', '=', 'propertys.id')
            ->where('propertys.status', 1)
            ->where('propertys.request_status', 'approved')
            ->where('propertys.propery_type', 1);

        $this->applyListingFilters($q, $filters);

        $rows = $q->orderByDesc('propertys.updated_at')->limit($limit)->get();

        return $rows->map(function ($p) {
            return [
                'id' => $p->id,
                'slug_id' => $p->slug_id,
                'title' => $p->title,
                'price' => $p->price,
                'property_type' => 'rent',
                'rent_duration' => $p->rentduration,
                'city' => $p->city,
                'state' => $p->state,
                'updated_at' => optional($p->updated_at)->toIso8601String(),
            ];
        })->values()->all();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $q
     * @param  array<string, mixed>  $filters
     */
    private function applyListingFilters($q, array $filters): void
    {
        if (! empty($filters['city_id'])) {
            $q->where('apl.city_id', $filters['city_id']);
        }
        if (! empty($filters['area_id'])) {
            $q->where('apl.area_id', $filters['area_id']);
        }
        if (! empty($filters['sub_area_id'])) {
            $q->where('apl.sub_area_id', $filters['sub_area_id']);
        }
        if (! empty($filters['bhk'])) {
            $q->whereExists(function ($sub) use ($filters) {
                $sub->select(DB::raw(1))
                    ->from('assign_parameters as ap')
                    ->whereColumn('ap.property_id', 'propertys.id')
                    ->where('ap.parameter_id', self::BEDROOM_PARAM_ID)
                    ->where('ap.value', (string) $filters['bhk']);
            });
        }
        if (! empty($filters['type_facet'])) {
            $facet = $filters['type_facet'];
            $q->where(function ($w) use ($facet) {
                if ($facet === 'pg') {
                    $w->where('propertys.title', 'like', '%pg%')
                        ->orWhere('propertys.title', 'like', '%PG%')
                        ->orWhereExists(function ($sub) {
                            $sub->select(DB::raw(1))
                                ->from('categories as c')
                                ->whereColumn('c.id', 'propertys.category_id')
                                ->where('c.category', 'like', '%PG%');
                        });
                } else {
                    $w->where('propertys.title', 'like', '%' . $facet . '%')
                        ->orWhere('propertys.title', 'like', '%' . strtoupper($facet) . '%');
                }
            });
        }
        if (array_key_exists('budget_min', $filters) || array_key_exists('budget_max', $filters)) {
            if ($filters['budget_min'] !== null) {
                $q->where('propertys.price', '>=', (int) $filters['budget_min']);
            }
            if ($filters['budget_max'] !== null) {
                $q->where('propertys.price', '<=', (int) $filters['budget_max']);
            }
        }
    }

    private function breadcrumbs(string $path): array
    {
        $web = rtrim((string) $this->settings->get('site_url', 'https://homes.sukoon.group'), '/');
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $crumbs = [['label' => 'Home', 'path' => '/']];
        $accum = '';
        foreach ($segments as $i => $segment) {
            $accum .= '/' . $segment;
            $label = ucwords(str_replace('-', ' ', $segment));
            if ($i === 1 && class_exists(City::class)) {
                $city = City::query()->where('slug', $segment)->value('name');
                if ($city) {
                    $label = $city;
                }
            }
            $crumbs[] = [
                'label' => $label,
                'path' => $accum . '/',
                'url' => $web . $accum . '/',
            ];
        }

        return $crumbs;
    }

    private function linkSet(SeoEnginePage $page, string $kind, int $limit): array
    {
        $path = $page->path;
        $query = SeoEnginePage::query()->where('path', '!=', $path);

        if ($kind === 'siblings') {
            $parent = $this->parentPath($path);
            $query->where('path', 'like', $parent . '%')
                ->whereRaw('LENGTH(path) - LENGTH(REPLACE(path, "/", "")) = ?', [
                    substr_count(rtrim($path, '/'), '/'),
                ]);
        } elseif ($kind === 'children') {
            $query->where('path', 'like', $path . '%')
                ->where('path', '!=', $path)
                ->whereRaw('LENGTH(path) - LENGTH(REPLACE(path, "/", "")) = ?', [
                    substr_count(rtrim($path, '/'), '/') + 1,
                ]);
        } elseif ($kind === 'budget') {
            $areaPrefix = $this->areaPrefix($path);
            if ($areaPrefix) {
                $query->where('page_type', 'rent_combo_budget')
                    ->where('path', 'like', $areaPrefix . '%');
            } else {
                return [];
            }
        }

        return $query->orderByDesc('listing_count')
            ->orderBy('path')
            ->limit($limit)
            ->get(['path', 'title', 'h1', 'listing_count', 'is_indexable'])
            ->map(fn ($p) => [
                'path' => $p->path,
                'title' => $p->title ?: $p->h1,
                'listing_count' => $p->listing_count,
                'is_indexable' => $p->is_indexable,
            ])
            ->values()
            ->all();
    }

    private function parentPath(string $path): string
    {
        $trimmed = rtrim($path, '/');
        $pos = strrpos($trimmed, '/');
        if ($pos === false) {
            return '/';
        }

        return substr($trimmed, 0, $pos + 1);
    }

    private function areaPrefix(string $path): ?string
    {
        if (! preg_match('#^/rent/([^/]+/[^/]+/)#', $path, $m)) {
            return null;
        }

        return '/rent/' . $m[1];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function localityStats(array $filters): array
    {
        $areaId = $filters['area_id'] ?? null;
        $subAreaId = $filters['sub_area_id'] ?? null;
        if (! $areaId) {
            return ['current' => null, 'series' => []];
        }

        $series = SeoEngineLocalityStat::query()
            ->where('area_id', $areaId)
            ->when($subAreaId, fn ($q) => $q->where('sub_area_id', $subAreaId))
            ->when(! $subAreaId, fn ($q) => $q->whereNull('sub_area_id'))
            ->orderByDesc('period')
            ->limit(6)
            ->get()
            ->sortBy('period')
            ->values();

        $current = $series->last();

        return [
            'current' => $current ? [
                'period' => $current->period,
                'avg_rent' => $current->avg_rent,
                'min_rent' => $current->min_rent,
                'max_rent' => $current->max_rent,
                'listing_count' => $current->listing_count,
                'bhk_mix' => $current->bhk_mix,
                'mom_change_pct' => $current->mom_change_pct,
            ] : null,
            'series' => $series->map(fn ($row) => [
                'period' => $row->period,
                'avg_rent' => $row->avg_rent,
                'listing_count' => $row->listing_count,
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function nearbyPlaces(array $filters): array
    {
        $areaId = $filters['area_id'] ?? null;
        if (! $areaId || ! class_exists(Area::class)) {
            return [];
        }

        if (class_exists(\App\Plugins\NearbyPlaces\Services\NearbyPlacesService::class)) {
            try {
                $service = app(\App\Plugins\NearbyPlaces\Services\NearbyPlacesService::class);
                if (method_exists($service, 'forArea')) {
                    return array_slice((array) $service->forArea((int) $areaId), 0, 6);
                }
            } catch (\Throwable) {
                // plugin optional
            }
        }

        return [];
    }

    private function comparison(SeoEnginePage $page, ?array $topSibling): ?array
    {
        if (! $topSibling) {
            return null;
        }

        $sibling = SeoEnginePage::query()->where('path', $topSibling['path'])->first();
        if (! $sibling) {
            return null;
        }

        return [
            'this' => [
                'path' => $page->path,
                'listing_count' => $page->listing_count,
                'quality_score' => $page->quality_score,
            ],
            'top_sibling' => [
                'path' => $sibling->path,
                'title' => $sibling->title,
                'listing_count' => $sibling->listing_count,
                'quality_score' => $sibling->quality_score,
            ],
            'listing_delta' => $page->listing_count - $sibling->listing_count,
        ];
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path .= '/';
        }

        return $path;
    }
}
