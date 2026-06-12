<?php

namespace App\Plugins\SeoEngine\Services;

use App\Plugins\AreaListing\Models\Area;
use App\Plugins\AreaListing\Models\City;
use App\Plugins\AreaListing\Models\SubArea;
use App\Plugins\SeoEngine\Models\SeoEngineLocalityStat;
use App\Plugins\SeoEngine\Models\SeoEnginePage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeoEnginePageGeneratorService
{
    private const BHK_FACETS = ['1bhk' => 1, '2bhk' => 2, '3bhk' => 3, '4bhk' => 4];

    private const BEDROOM_PARAM_ID = 1;

    public function __construct(
        private SeoEngineSettingsService $settings,
        private SeoEngineTemplateService $templates
    ) {
    }

    public function generate(): array
    {
        if (! class_exists(City::class)) {
            throw new \RuntimeException('AreaListing plugin is required for page generation.');
        }

        $this->templates->seedDefaultsIfEmpty();
        $typeFacets = $this->typeFacets();
        $budgetBands = $this->budgetBands();
        $stats = [
            'rent_city' => 0,
            'rent_area' => 0,
            'rent_subarea' => 0,
            'rent_combo_bhk' => 0,
            'rent_combo_type' => 0,
            'rent_combo_budget' => 0,
            'indexable' => 0,
            'not_indexable' => 0,
            'locality_stats' => 0,
        ];

        $cities = City::query()->where('status', true)->orderBy('name')->get();
        foreach ($cities as $city) {
            $stats['rent_city'] += $this->upsertLocationPage('rent_city', "/rent/{$city->slug}/", [
                'city' => $city->name,
                'city_slug' => $city->slug,
                'city_id' => $city->id,
            ], ['city_id' => $city->id], $stats);

            $areas = Area::query()->where('city_id', $city->id)->where('status', true)->get();
            foreach ($areas as $area) {
                $areaSlug = $area->slug ?: Str::slug($area->name);
                $stats['rent_area'] += $this->upsertLocationPage('rent_area', "/rent/{$city->slug}/{$areaSlug}/", [
                    'city' => $city->name,
                    'city_slug' => $city->slug,
                    'area' => $area->name,
                    'area_slug' => $areaSlug,
                    'city_id' => $city->id,
                    'area_id' => $area->id,
                ], ['city_id' => $city->id, 'area_id' => $area->id], $stats);

                $stats['locality_stats'] += $this->refreshLocalityStat($area->id, null, $city->id);

                foreach (self::BHK_FACETS as $facet => $beds) {
                    $stats['rent_combo_bhk'] += $this->upsertComboPage(
                        'rent_combo_bhk',
                        "/rent/{$city->slug}/{$areaSlug}/{$facet}/",
                        ['city' => $city->name, 'area' => $area->name, 'type' => strtoupper($facet)],
                        ['city_id' => $city->id, 'area_id' => $area->id, 'bhk' => $beds],
                        $stats
                    );
                }
                foreach ($typeFacets as $typeFacet) {
                    $stats['rent_combo_type'] += $this->upsertComboPage(
                        'rent_combo_type',
                        "/rent/{$city->slug}/{$areaSlug}/{$typeFacet}/",
                        ['city' => $city->name, 'area' => $area->name, 'type' => $this->typeFacetLabel($typeFacet)],
                        ['city_id' => $city->id, 'area_id' => $area->id, 'type_facet' => $typeFacet],
                        $stats
                    );
                }
                foreach ($budgetBands as $band) {
                    $bandSlug = $this->budgetBandSlug($band);
                    $stats['rent_combo_budget'] += $this->upsertComboPage(
                        'rent_combo_budget',
                        "/rent/{$city->slug}/{$areaSlug}/{$bandSlug}/",
                        [
                            'city' => $city->name,
                            'area' => $area->name,
                            'band_label' => $band['label'] ?? '',
                            'min_rent' => $this->formatBandAmount($band['min'] ?? null),
                            'max_rent' => $this->formatBandAmount($band['max'] ?? null),
                        ],
                        [
                            'city_id' => $city->id,
                            'area_id' => $area->id,
                            'budget_min' => $band['min'] ?? null,
                            'budget_max' => $band['max'] ?? null,
                        ],
                        $stats
                    );
                }

                $subAreas = SubArea::query()->where('area_id', $area->id)->get();
                foreach ($subAreas as $sub) {
                    $subSlug = $sub->slug ?: Str::slug($sub->name);
                    $stats['rent_subarea'] += $this->upsertLocationPage('rent_subarea', "/rent/{$city->slug}/{$areaSlug}/{$subSlug}/", [
                        'city' => $city->name,
                        'area' => $area->name,
                        'subarea' => $sub->name,
                        'top_landmark' => $area->name,
                    ], ['city_id' => $city->id, 'area_id' => $area->id, 'sub_area_id' => $sub->id], $stats);
                    $stats['locality_stats'] += $this->refreshLocalityStat($area->id, $sub->id, $city->id);
                }
            }
        }

        $this->settings->set('cron_last_generate_pages_at', now()->toIso8601String(), 'cron');

        return $stats;
    }

    private function upsertLocationPage(string $pageType, string $path, array $labels, array $filters, array &$stats): int
    {
        return $this->upsertPage($pageType, $path, $labels, $filters, $stats) ? 1 : 0;
    }

    private function upsertComboPage(string $pageType, string $path, array $labels, array $filters, array &$stats): int
    {
        return $this->upsertPage($pageType, $path, $labels, $filters, $stats) ? 1 : 0;
    }

    private function upsertPage(string $pageType, string $path, array $labels, array $filters, array &$stats): bool
    {
        $existing = SeoEnginePage::query()->where('path', $path)->first();
        if ($existing?->lock_content) {
            return false;
        }

        $metrics = $this->countListings($filters);
        $threshold = (int) $this->settings->get('index_threshold', 3);
        $quality = $this->qualityScore($metrics);
        $indexable = $metrics['count'] >= $threshold && $quality >= 50;

        $renderVars = $this->buildRenderVars($pageType, $labels, $metrics);
        $meta = $this->templates->render($pageType, $renderVars);

        SeoEnginePage::query()->updateOrCreate(
            ['path' => $path],
            [
                'page_type' => $pageType,
                'params' => $filters,
                'title' => $meta['title'],
                'h1' => $meta['h1'],
                'meta_description' => $meta['meta_description'],
                'listing_count' => $metrics['count'],
                'quality_score' => $quality,
                'is_indexable' => $indexable,
            ]
        );

        $indexable ? $stats['indexable']++ : $stats['not_indexable']++;

        return true;
    }

  /**
   * @return array{count:int,avg_rent:?int,min_rent:?int,max_rent:?int}
   */
    public function countListings(array $filters): array
    {
        $q = DB::table('propertys as p')
            ->join('area_listing_property_locations as apl', 'apl.property_id', '=', 'p.id')
            ->where('p.status', 1)
            ->where('p.request_status', 'approved')
            ->where('p.propery_type', 1);

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
                    ->whereColumn('ap.property_id', 'p.id')
                    ->where('ap.parameter_id', self::BEDROOM_PARAM_ID)
                    ->where('ap.value', (string) $filters['bhk']);
            });
        }
        if (! empty($filters['type_facet'])) {
            $this->applyTypeFacetFilter($q, $filters['type_facet']);
        }
        if (array_key_exists('budget_min', $filters) || array_key_exists('budget_max', $filters)) {
            if ($filters['budget_min'] !== null) {
                $q->where('p.price', '>=', (int) $filters['budget_min']);
            }
            if ($filters['budget_max'] !== null) {
                $q->where('p.price', '<=', (int) $filters['budget_max']);
            }
        }

        $row = $q->selectRaw('COUNT(*) as cnt, AVG(p.price) as avg_price, MIN(p.price) as min_price, MAX(p.price) as max_price')->first();

        return [
            'count' => (int) ($row->cnt ?? 0),
            'avg_rent' => $row->avg_price ? (int) round($row->avg_price) : null,
            'min_rent' => $row->min_price ? (int) $row->min_price : null,
            'max_rent' => $row->max_price ? (int) $row->max_price : null,
        ];
    }

    private function qualityScore(array $metrics): int
    {
        $count = $metrics['count'];
        if ($count === 0) {
            return 0;
        }
        if ($count < 3) {
            $score = 40;
        } elseif ($count < 6) {
            $score = 70;
        } else {
            $score = 90;
        }
        if ($metrics['avg_rent']) {
            $score = min(100, $score + 10);
        }

        return $score;
    }

    private function refreshLocalityStat(int $areaId, ?int $subAreaId, int $cityId): int
    {
        $filters = ['city_id' => $cityId, 'area_id' => $areaId];
        if ($subAreaId) {
            $filters['sub_area_id'] = $subAreaId;
        }
        $metrics = $this->countListings($filters);
        $period = now()->format('Y-m');

        $bhkMix = [];
        foreach (self::BHK_FACETS as $label => $beds) {
            $f = $filters;
            $f['bhk'] = $beds;
            $bhkMix[$label] = $this->countListings($f)['count'];
        }

        SeoEngineLocalityStat::query()->updateOrCreate(
            ['area_id' => $areaId, 'sub_area_id' => $subAreaId, 'period' => $period],
            [
                'avg_rent' => $metrics['avg_rent'],
                'min_rent' => $metrics['min_rent'],
                'max_rent' => $metrics['max_rent'],
                'listing_count' => $metrics['count'],
                'bhk_mix' => $bhkMix,
                'mom_change_pct' => null,
            ]
        );

        return 1;
    }

    /**
     * @return list<string>
     */
    private function typeFacets(): array
    {
        $facets = $this->settings->get('type_facets', ['flat', 'house', 'apartment', 'pg']);
        if (! is_array($facets)) {
            return ['flat', 'house', 'apartment', 'pg'];
        }

        return array_values(array_filter(array_map(
            fn ($f) => Str::slug((string) $f),
            $facets
        )));
    }

    /**
     * @return list<array{label:string,min:?int,max:?int}>
     */
    private function budgetBands(): array
    {
        $bands = $this->settings->get('budget_bands', []);
        if (! is_array($bands)) {
            return [];
        }

        return array_values(array_filter($bands, fn ($band) => ! empty($band['label'])));
    }

    private function typeFacetLabel(string $facet): string
    {
        return match ($facet) {
            'pg' => 'PG',
            default => ucfirst($facet),
        };
    }

    /**
     * @param  array{label?:string,min?:int|null,max?:int|null}  $band
     */
    private function budgetBandSlug(array $band): string
    {
        $slug = Str::slug($band['label'] ?? '');
        if ($slug !== '') {
            return $slug;
        }

        $min = $band['min'] ?? null;
        $max = $band['max'] ?? null;
        if ($max !== null && ($min === null || $min === 0)) {
            return 'under-rs' . ($max + 1);
        }
        if ($min !== null && $max !== null) {
            return 'rs' . $min . '-' . $max;
        }
        if ($min !== null) {
            return 'rs' . $min . '-plus';
        }

        return 'budget';
    }

    private function formatBandAmount(mixed $amount): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        return (string) (int) $amount;
    }

    /**
     * @param  array<string, mixed>  $labels
     * @param  array{count:int,avg_rent:?int,min_rent:?int,max_rent:?int}  $metrics
     * @return array<string, mixed>
     */
    private function buildRenderVars(string $pageType, array $labels, array $metrics): array
    {
        $vars = array_merge($labels, [
            'count' => $metrics['count'],
            'avg_rent' => $metrics['avg_rent'] ?? '',
        ]);

        if ($pageType === 'rent_combo_budget') {
            return $vars;
        }

        $vars['min_rent'] = $metrics['min_rent'] ?? '';
        $vars['max_rent'] = $metrics['max_rent'] ?? '';

        return $vars;
    }

    private function applyTypeFacetFilter($q, string $facet): void
    {
        if ($facet === 'pg') {
            $q->where(function ($w) {
                $w->where('p.title', 'like', '%pg%')
                    ->orWhere('p.title', 'like', '%PG%')
                    ->orWhere('p.title', 'like', '%Paying Guest%')
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('categories as c')
                            ->whereColumn('c.id', 'p.category_id')
                            ->where('c.category', 'like', '%PG%');
                    });
            });

            return;
        }

        $q->where(function ($w) use ($facet) {
            $w->where('p.title', 'like', '%' . $facet . '%')
                ->orWhere('p.title', 'like', '%' . strtoupper($facet) . '%');
        });
    }
}
