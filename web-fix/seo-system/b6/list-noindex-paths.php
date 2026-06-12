<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo implode("\n", App\Plugins\SeoEngine\Models\SeoEnginePage::query()
    ->where('is_indexable', false)
    ->orderBy('path')
    ->limit(8)
    ->pluck('path')
    ->all()) . "\n";
