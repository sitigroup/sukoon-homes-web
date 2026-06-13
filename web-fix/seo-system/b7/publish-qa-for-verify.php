<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEngineQaPage;
use App\Plugins\SeoEngine\Services\SeoEngineQaSitemapService;

$id = (int) ($argv[1] ?? 1);
$page = SeoEngineQaPage::query()->find($id);
if (! $page) {
    fwrite(STDERR, "Guide id {$id} not found\n");
    exit(1);
}

$page->update([
    'status' => 'published',
    'direct_answer' => $page->direct_answer ?: 'Stamp duty on Rajasthan rent agreements depends on term length and annual rent; buy e-stamp via the state portal before registration.',
    'body_html' => $page->body_html ?: '<p>Rajasthan rent agreements typically require stamp duty under the Indian Stamp Act. Rates vary by lease term and rent amount. Use the Rajasthan e-stamp portal or authorised vendors, then register at the sub-registrar if required for your agreement value.</p>',
]);

app(SeoEngineQaSitemapService::class)->export();
Illuminate\Support\Facades\Cache::forget('seo_engine:api:qa:' . $page->category . ':' . $page->slug);
Illuminate\Support\Facades\Cache::forget('seo_engine:api:qa_sitemap');

echo json_encode([
    'published' => $page->fresh()->only(['id', 'category', 'slug', 'status']),
    'path' => $page->fresh()->publicPath(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
