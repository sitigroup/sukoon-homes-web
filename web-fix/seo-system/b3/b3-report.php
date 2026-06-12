<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEngineLocalityStat;
use App\Plugins\SeoEngine\Models\SeoEnginePage;

$byType = SeoEnginePage::query()
    ->selectRaw('page_type, COUNT(*) as cnt')
    ->groupBy('page_type')
    ->pluck('cnt', 'page_type');

$indexable = SeoEnginePage::query()->where('is_indexable', true)->count();
$notIndexable = SeoEnginePage::query()->where('is_indexable', false)->count();

$sampleTitles = SeoEnginePage::query()
    ->orderBy('id')
    ->limit(5)
    ->pluck('title', 'path');

$localityRows = SeoEngineLocalityStat::query()->count();

echo json_encode([
    'pages_by_type' => $byType,
    'indexable' => $indexable,
    'not_indexable' => $notIndexable,
    'sample_titles' => $sampleTitles,
    'locality_stats_rows' => $localityRows,
], JSON_PRETTY_PRINT) . PHP_EOL;
