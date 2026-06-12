<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEngineContentFailure;
use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Services\SeoEngineContentService;

$failures = SeoEngineContentFailure::query()->orderByDesc('id')->limit(5)->get(['path','provider','error','created_at']);
echo "FAILURES:\n" . json_encode($failures, JSON_PRETTY_PRINT) . "\n\n";

$page = SeoEnginePage::query()->where('path', '/rent/barmer/')->first();
if ($page) {
    $svc = app(SeoEngineContentService::class);
    $ok = $svc->generateForPage($page);
    echo "single /rent/barmer/ ok=" . ($ok ? 'yes' : 'no') . "\n";
    $last = SeoEngineContentFailure::query()->orderByDesc('id')->first();
    echo "last error: " . ($last->error ?? 'none') . "\n";
}
