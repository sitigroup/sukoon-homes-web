<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEnginePage;

$paths = [
    '/rent/barmer/',
    '/rent/barmer/kailash-puri/',
    '/rent/barmer/mahaveer-nagar/',
    '/rent/barmer/mayapuri/',
];

$updated = SeoEnginePage::query()
    ->whereIn('path', $paths)
    ->where('content_review_status', 'pending')
    ->update(['content_review_status' => 'approved']);

echo "Approved {$updated} page(s)\n";
