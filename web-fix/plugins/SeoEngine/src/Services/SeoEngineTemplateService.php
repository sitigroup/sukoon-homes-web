<?php

namespace App\Plugins\SeoEngine\Services;

use App\Plugins\SeoEngine\Models\SeoEngineTemplateVersion;

class SeoEngineTemplateService
{
    public const PAGE_TYPES = [
        'rent_city',
        'rent_area',
        'rent_subarea',
        'rent_combo_bhk',
        'rent_combo_type',
        'rent_combo_budget',
    ];

    public const VARIABLES = [
        '{count}', '{type}', '{area}', '{city}', '{subarea}',
        '{min_rent}', '{max_rent}', '{avg_rent}', '{top_landmark}',
    ];

    public function defaults(): array
    {
        return [
            'rent_city' => [
                'title_template' => 'Flats & Houses for Rent in {city} | Sukoon Homes',
                'h1_template' => 'Homes for Rent in {city}',
                'meta_description_template' => 'Browse {count} verified rental listings in {city}. Average rent from ₹{min_rent}. Find flats and houses on Sukoon Homes.',
            ],
            'rent_area' => [
                'title_template' => 'Rent in {area}, {city} — {count} Listings | Sukoon Homes',
                'h1_template' => 'Rental Homes in {area}, {city}',
                'meta_description_template' => '{count} homes for rent in {area}, {city}. Avg rent ₹{avg_rent}. Verified listings with online agreements.',
            ],
            'rent_subarea' => [
                'title_template' => '{subarea} Rentals, {area} {city} | Sukoon Homes',
                'h1_template' => 'Rent in {subarea}, {area}',
                'meta_description_template' => 'Explore {count} rentals in {subarea}, {area}. Near {top_landmark}. From ₹{min_rent} on Sukoon Homes.',
            ],
            'rent_combo_bhk' => [
                'title_template' => '{type} for Rent in {area}, {city} — {count} Homes',
                'h1_template' => '{type} Rentals in {area}',
                'meta_description_template' => '{count} {type} for rent in {area}, {city}. Average rent ₹{avg_rent}. Verified on Sukoon Homes.',
            ],
            'rent_combo_type' => [
                'title_template' => '{type} for Rent in {area}, {city} | Sukoon Homes',
                'h1_template' => '{type} in {area}, {city}',
                'meta_description_template' => 'Find {count} {type} rentals in {area}. Avg ₹{avg_rent}. Sukoon verified listings.',
            ],
            'rent_combo_budget' => [
                'title_template' => 'Rent under ₹{max_rent} in {area}, {city} | Sukoon Homes',
                'h1_template' => 'Budget Rentals in {area}',
                'meta_description_template' => '{count} homes for rent in {area} from ₹{min_rent} to ₹{max_rent}. Verified listings on Sukoon Homes.',
            ],
        ];
    }

    public function latest(string $pageType): ?SeoEngineTemplateVersion
    {
        return SeoEngineTemplateVersion::query()
            ->where('page_type', $pageType)
            ->orderByDesc('id')
            ->first();
    }

    public function history(string $pageType, int $limit = 5)
    {
        return SeoEngineTemplateVersion::query()
            ->where('page_type', $pageType)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function save(string $pageType, array $templates): SeoEngineTemplateVersion
    {
        $version = SeoEngineTemplateVersion::query()->create([
            'page_type' => $pageType,
            'title_template' => $templates['title_template'],
            'h1_template' => $templates['h1_template'],
            'meta_description_template' => $templates['meta_description_template'] ?? null,
        ]);

        $stale = SeoEngineTemplateVersion::query()
            ->where('page_type', $pageType)
            ->orderByDesc('id')
            ->skip(5)
            ->pluck('id');
        if ($stale->isNotEmpty()) {
            SeoEngineTemplateVersion::query()->whereIn('id', $stale)->delete();
        }

        return $version;
    }

    public function render(string $pageType, array $vars): array
    {
        $tpl = $this->latest($pageType)?->toArray() ?? ($this->defaults()[$pageType] ?? []);
        $replace = [];
        foreach ($vars as $key => $value) {
            $replace['{' . $key . '}'] = (string) ($value ?? '');
        }

        return [
            'title' => strtr($tpl['title_template'] ?? '', $replace),
            'h1' => strtr($tpl['h1_template'] ?? '', $replace),
            'meta_description' => strtr($tpl['meta_description_template'] ?? '', $replace),
        ];
    }

    public function seedDefaultsIfEmpty(): void
    {
        foreach ($this->defaults() as $pageType => $templates) {
            if (! $this->latest($pageType)) {
                $this->save($pageType, $templates);
            }
        }
    }
}
