<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Models\SeoEngineQaPage;

$published = SeoEngineQaPage::query()->where('status', 'published')->get(['id', 'category', 'slug', 'status', 'question']);
$page8 = SeoEngineQaPage::query()->find(8);

echo json_encode([
    'page_8' => $page8 ? $page8->only(['id', 'category', 'slug', 'status', 'question']) : null,
    'published_guides' => $published->map(fn ($p) => [
        'id' => $p->id,
        'path' => $p->publicPath(),
        'status' => $p->status,
    ])->values()->all(),
    'rent_pages_total' => SeoEnginePage::query()->count(),
    'rent_indexable' => SeoEnginePage::query()->where('is_indexable', true)->count(),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
