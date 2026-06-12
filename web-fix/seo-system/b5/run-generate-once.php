<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Services\SeoEnginePageGeneratorService;
use App\Plugins\SeoEngine\Services\SeoEnginePageDataService;

$junk = ['baldev-nagar-barmer', 'raj-colneyer', 'saadsd'];
$gen = app(SeoEnginePageGeneratorService::class);
$stats = $gen->generate();

$junkFound = SeoEnginePage::query()->where(function ($q) use ($junk) {
    foreach ($junk as $p) {
        $q->orWhere('path', 'like', '%' . $p . '%');
    }
})->pluck('path');

$payload = app(SeoEnginePageDataService::class)->getByPath('/rent/barmer/');
$listings = array_map(fn ($l) => ['title' => $l['title'], 'city' => $l['city']], $payload['listings'] ?? []);

echo json_encode([
    'stats' => $stats,
    'total' => SeoEnginePage::query()->count(),
    'junk_count' => count($junkFound),
    'junk_paths' => $junkFound,
    'listings' => $listings,
], JSON_PRETTY_PRINT) . PHP_EOL;
