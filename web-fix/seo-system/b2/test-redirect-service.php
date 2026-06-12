<?php
/**
 * Standalone redirect service tests (no PHPUnit bootstrap required).
 */
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEngineRedirect;
use App\Plugins\SeoEngine\Services\SeoEngineRedirectService;

$service = $app->make(SeoEngineRedirectService::class);
$fail = 0;

SeoEngineRedirect::query()->where('from_path', 'like', '/property-details/test-%')->delete();

$service->upsertRedirect('/property-details/test-a/', '/property-details/test-b/', 301);
$service->upsertRedirect('/property-details/test-b/', '/property-details/test-c/', 301);
$rowA = SeoEngineRedirect::query()->where('from_path', '/property-details/test-a/')->first();
if (($rowA->to_path ?? '') !== '/property-details/test-c/') {
    echo "FAIL chain flatten\n";
    $fail++;
} else {
    echo "PASS chain flatten\n";
}

$service->upsertRedirect('/property-details/test-x/', '/property-details/test-y/', 301);
$blocked = $service->upsertRedirect('/property-details/test-y/', '/property-details/test-x/', 301);
if ($blocked !== null) {
    echo "FAIL loop prevention\n";
    $fail++;
} else {
    echo "PASS loop prevention\n";
}

$redirect = $service->upsertRedirect('/property-details/test-old/', '/property-details/test-new/', 301);
$before = (int) $redirect->hits;
$service->incrementHit($redirect);
$after = (int) $redirect->fresh()->hits;
if ($after !== $before + 1) {
    echo "FAIL hit increment\n";
    $fail++;
} else {
    echo "PASS hit increment\n";
}

SeoEngineRedirect::query()->where('from_path', 'like', '/property-details/test-%')->delete();
exit($fail > 0 ? 1 : 0);
