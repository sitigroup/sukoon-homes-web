<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEngineContentFailure;
use App\Plugins\SeoEngine\Models\SeoEnginePage;

$withContent = SeoEnginePage::query()->whereNotNull('intro_html')->where('intro_html', '!=', '')->count();
$eligible = SeoEnginePage::query()->where('listing_count', '>=', 1)->count();
$approved = SeoEnginePage::query()->where('content_review_status', 'approved')->count();
$pending = SeoEnginePage::query()->where('content_review_status', 'pending')->count();
$failures = SeoEngineContentFailure::query()->count();

echo json_encode(compact('withContent', 'eligible', 'approved', 'pending', 'failures'), JSON_PRETTY_PRINT) . PHP_EOL;
