<?php
/**
 * B3 post-deploy report — run on admin-homes server.
 */
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Property;
use App\Plugins\SeoEngine\Models\SeoEngineLocalityStat;
use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Services\SeoEngineIndexNowService;
use App\Plugins\SeoEngine\Services\SeoEngineSettingsService;
use Illuminate\Support\Facades\DB;

$locationTypes = ['rent_city', 'rent_area', 'rent_subarea'];
$comboTypes = ['rent_combo_bhk', 'rent_combo_type'];
$budgetTypes = ['rent_combo_budget'];

$byType = SeoEnginePage::query()
    ->selectRaw('page_type, COUNT(*) as cnt')
    ->groupBy('page_type')
    ->pluck('cnt', 'page_type')
    ->all();

$rollup = [
    'location' => 0,
    'combo' => 0,
    'budget' => 0,
];
foreach ($locationTypes as $t) {
    $rollup['location'] += (int) ($byType[$t] ?? 0);
}
foreach ($comboTypes as $t) {
    $rollup['combo'] += (int) ($byType[$t] ?? 0);
}
foreach ($budgetTypes as $t) {
    $rollup['budget'] += (int) ($byType[$t] ?? 0);
}

$indexable = SeoEnginePage::query()->where('is_indexable', true)->count();
$notIndexable = SeoEnginePage::query()->where('is_indexable', false)->count();

$sampleTitles = SeoEnginePage::query()
    ->orderBy('listing_count', 'desc')
    ->orderBy('id')
    ->limit(5)
    ->get(['path', 'title', 'page_type', 'listing_count', 'is_indexable'])
    ->map(fn ($p) => [
        'path' => $p->path,
        'type' => $p->page_type,
        'title' => $p->title,
        'listings' => $p->listing_count,
        'indexable' => $p->is_indexable,
    ])
    ->values()
    ->all();

$localityRows = SeoEngineLocalityStat::query()->count();

$rentSitemapFile = storage_path('app/seo-engine/rent-pages.json');
$rentSitemapCount = 0;
if (is_file($rentSitemapFile)) {
    $rentSitemapCount = count(json_decode(file_get_contents($rentSitemapFile), true) ?: []);
}

// IndexNow test — use inactive property URL (no live traffic impact)
$settings = app(SeoEngineSettingsService::class);
$key = (string) $settings->get('indexnow_key', '');
$indexNow = app(SeoEngineIndexNowService::class);
$testUrl = 'https://homes.sukoon.group/property-details/3-bhk-flat-for-rent-b2verify-test/';
$indexNowResult = [
    'key_configured' => $key !== '',
    'ping_attempted' => false,
    'ping_ok' => null,
    'note' => $key === '' ? 'Skipped — indexnow_key not set in SEO Engine settings' : '',
];
if ($key !== '') {
    $indexNowResult['ping_attempted'] = true;
    $indexNowResult['ping_ok'] = $indexNow->pingUrl($testUrl);
}

// PG category check
$pgCategory = DB::table('categories')
    ->where('category', 'like', '%PG%')
    ->orWhere('category', 'like', '%pg%')
    ->orWhere('category', 'like', '%Paying Guest%')
    ->get(['id', 'category']);

$pgInProperties = Property::query()
    ->where('status', 1)
    ->where('request_status', 'approved')
    ->where(function ($q) {
        $q->where('title', 'like', '%PG%')
            ->orWhere('title', 'like', '%pg%')
            ->orWhere('title', 'like', '%Paying Guest%');
    })
    ->count();

$pgFacetPages = SeoEnginePage::query()
    ->where('page_type', 'rent_combo_type')
    ->where('path', 'like', '%/pg/%')
    ->count();

$typeFacets = $settings->get('type_facets', ['flat', 'house', 'apartment', 'pg']);
if (! is_array($typeFacets)) {
    $typeFacets = ['flat', 'house', 'apartment', 'pg'];
}

echo json_encode([
    'pages_by_type' => $byType,
    'rollup' => $rollup,
    'total_pages' => array_sum($byType),
    'indexable' => $indexable,
    'not_indexable' => $notIndexable,
    'sample_titles' => $sampleTitles,
    'locality_stats_rows' => $localityRows,
    'rent_sitemap_url_count' => $rentSitemapCount,
    'indexnow' => $indexNowResult,
    'pg_category' => [
        'categories_table' => $pgCategory,
        'active_listings_title_match' => $pgInProperties,
        'generator_type_facets' => $typeFacets,
        'pg_facet_pages_generated' => $pgFacetPages,
        'pg_in_generator' => in_array('pg', $typeFacets, true),
    ],
], JSON_PRETTY_PRINT) . PHP_EOL;
