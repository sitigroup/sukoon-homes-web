<?php
/**
 * Repair /rent/ redirect targets + remove stale baldev-nagar-barmer pages.
 */
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Services\SeoEngineRedirectService;

$redirects = app(SeoEngineRedirectService::class);
$fixed = $redirects->repairRegistryTargets();

$stale = SeoEnginePage::query()->where('path', 'like', '/rent/baldev-nagar-barmer%')->delete();

echo json_encode([
    'redirects_repaired' => $fixed,
    'stale_pages_deleted' => $stale,
    'remaining_stale' => SeoEnginePage::query()->where('path', 'like', '/rent/baldev-nagar-barmer%')->count(),
], JSON_PRETTY_PRINT) . PHP_EOL;
