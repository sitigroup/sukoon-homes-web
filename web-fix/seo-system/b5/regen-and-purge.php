<?php
/**
 * Generate pages, build sitemap, purge stale registry paths with redirects.
 */
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Services\SeoEngineRedirectService;
use Illuminate\Support\Facades\Artisan;

$junk = ['baldev-nagar-barmer', 'raj-colneyer', 'saadsd'];
$redirects = app(SeoEngineRedirectService::class);
$removed = [];

Artisan::call('seo-engine:generate-pages');
Artisan::call('seo-engine:build-sitemaps');

// Purge AFTER generate — inactive/junk Area Wise rows may still produce paths until admin deletes them.
$stale = SeoEnginePage::query()
    ->where(function ($q) use ($junk) {
        foreach ($junk as $p) {
            $q->orWhere('path', 'like', '%' . $p . '%');
        }
    })
    ->get();

foreach ($stale as $page) {
    $target = '/rent/barmer/';
    if (preg_match('#^/rent/([^/]+)/#', $page->path, $m) && $m[1] !== 'baldev-nagar-barmer') {
        $candidate = '/rent/' . $m[1] . '/';
        if (SeoEnginePage::query()->where('path', $candidate)->exists()) {
            $target = $candidate;
        }
    }
    $redirects->upsertRedirect($page->path, $target, 301);
    $page->delete();
    $removed[] = ['path' => $page->path, 'redirect_to' => $target];
}

Artisan::call('seo-engine:build-sitemaps');

$byType = SeoEnginePage::query()->selectRaw('page_type, COUNT(*) c')->groupBy('page_type')->pluck('c', 'page_type');
$indexable = SeoEnginePage::query()->where('is_indexable', true)->orderBy('path')->pluck('path');
$remaining = SeoEnginePage::query()
    ->where(function ($q) use ($junk) {
        foreach ($junk as $p) {
            $q->orWhere('path', 'like', '%' . $p . '%');
        }
    })
    ->pluck('path');

echo json_encode([
    'removed_stale' => $removed,
    'pages_by_type' => $byType,
    'total' => $byType->sum(),
    'indexable_paths' => $indexable,
    'remaining_junk_paths' => $remaining,
    'rent_sitemap' => json_decode(file_get_contents(storage_path('app/seo-engine/rent-pages.json')), true),
], JSON_PRETTY_PRINT) . PHP_EOL;
