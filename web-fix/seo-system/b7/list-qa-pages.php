<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEngineQaPage;

$rows = SeoEngineQaPage::query()->orderBy('id')->get(['id', 'category', 'slug', 'status', 'question']);
echo json_encode($rows->map(fn ($r) => [
    'id' => $r->id,
    'status' => $r->status,
    'path' => $r->publicPath(),
    'question' => mb_substr($r->question, 0, 60),
])->values()->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
