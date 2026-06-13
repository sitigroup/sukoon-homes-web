<?php
/**
 * Verify QA status toggle + sitemap export (run on admin-homes server).
 */
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEngineQaPage;
use App\Plugins\SeoEngine\Services\SeoEngineQaSitemapService;

$id = (int) ($argv[1] ?? 1);
$page = SeoEngineQaPage::query()->find($id);
if (! $page) {
    fwrite(STDERR, "Guide {$id} not found\n");
    exit(1);
}

$sitemap = app(SeoEngineQaSitemapService::class);
$out = ['id' => $id, 'steps' => []];

foreach (['draft', 'published', 'draft'] as $status) {
    $page->update(['status' => $status]);
    $export = $sitemap->export();
    $json = json_decode(@file_get_contents(storage_path('app/seo-engine/qa-guides.json')), true) ?: [];
    $paths = array_column($json, 'path');
    $inSitemap = in_array($page->fresh()->publicPath(), $paths, true);

    $out['steps'][] = [
        'status' => $status,
        'db_status' => $page->fresh()->status,
        'sitemap_written' => $export['written'] ?? true,
        'sitemap_count' => $export['count'] ?? 0,
        'in_sitemap' => $inSitemap,
    ];
}

$out['pass'] = collect($out['steps'])->every(function ($step) {
    if ($step['db_status'] !== $step['status']) {
        return false;
    }
    if ($step['status'] === 'published') {
        return $step['in_sitemap'] === true && ($step['sitemap_written'] ?? false);
    }

    return $step['in_sitemap'] === false && ($step['sitemap_written'] ?? false);
});

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($out['pass'] ? 0 : 1);
