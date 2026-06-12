<?php
/**
 * B3 review post-deploy — templates, cache, IndexNow, regenerate, band check.
 */
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Services\SeoEngineIndexNowService;
use App\Plugins\SeoEngine\Services\SeoEngineSettingsService;
use App\Plugins\SeoEngine\Services\SeoEngineTemplateService;
use Illuminate\Support\Facades\Artisan;

$settings = app(SeoEngineSettingsService::class);
$settings->clearCache();

app(SeoEngineTemplateService::class)->publishDefaults();

$key = (string) $settings->get('indexnow_key', '');
if ($key === '') {
    $key = bin2hex(random_bytes(16));
    $settings->set('indexnow_key', $key, 'bots');
}
$homesPublic = '/www/wwwroot/homes.sukoon.group/public';
$keyFile = $homesPublic . '/' . $key . '.txt';
file_put_contents($keyFile, $key);
@chown($keyFile, 'www');
@chgrp($keyFile, 'www');

Artisan::call('seo-engine:generate-pages');
Artisan::call('seo-engine:build-sitemaps');
@chown('/www/wwwroot/admin-homes/storage/app/seo-engine', 'www');
@chgrp('/www/wwwroot/admin-homes/storage/app/seo-engine', 'www');
@chmod('/www/wwwroot/admin-homes/storage/app/seo-engine', 0775);
@chown('/www/wwwroot/admin-homes/storage/app/seo-engine/rent-pages.json', 'www');
@chgrp('/www/wwwroot/admin-homes/storage/app/seo-engine/rent-pages.json', 'www');

$budgetSample = SeoEnginePage::query()
    ->where('page_type', 'rent_combo_budget')
    ->where('listing_count', '>', 0)
    ->orderByDesc('listing_count')
    ->first(['path', 'title', 'params']);

$areaSample = SeoEnginePage::query()
    ->where('page_type', 'rent_area')
    ->where('listing_count', 1)
    ->first(['path', 'title']);

$indexNow = app(SeoEngineIndexNowService::class);
$pingUrl = 'https://homes.sukoon.group/rent/baldev-nagar-barmer/';
$pingOk = $indexNow->pingUrl($pingUrl);

echo json_encode([
    'commit' => '136843a',
    'budget_bands' => $settings->get('budget_bands'),
    'type_facets' => $settings->get('type_facets'),
    'indexnow_key_set' => true,
    'indexnow_key_file' => 'https://homes.sukoon.group/' . $key . '.txt',
    'indexnow_ping' => ['url' => $pingUrl, 'ok' => $pingOk],
    'budget_sample' => $budgetSample?->only(['path', 'title', 'params']),
    'plural_sample_count_1' => $areaSample?->only(['path', 'title']),
    'pg_pages' => SeoEnginePage::query()->where('page_type', 'rent_combo_type')->where('path', 'like', '%/pg/%')->count(),
    'generate_output' => trim(Artisan::output()),
], JSON_PRETTY_PRINT) . PHP_EOL;
