<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEngineQaPage;

$drafts = SeoEngineQaPage::query()->where('status', 'draft')->count();
$total = SeoEngineQaPage::query()->count();

echo json_encode(['qa_total' => $total, 'qa_drafts' => $drafts], JSON_PRETTY_PRINT) . PHP_EOL;
