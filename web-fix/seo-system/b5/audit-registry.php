<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEnginePage;

$junk = ['baldev-nagar-barmer', 'raj-colneyer', 'saadsd'];
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
    'pages_by_type' => $byType,
    'total' => (int) $byType->sum(),
    'indexable_paths' => $indexable,
    'remaining_junk_paths' => $remaining,
], JSON_PRETTY_PRINT) . PHP_EOL;
