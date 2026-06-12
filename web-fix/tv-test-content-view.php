<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $blocks = App\Plugins\TrustVerification\Models\TvContentBlock::query()
        ->where('group_key', 'hub')
        ->orderBy('sort_order')
        ->get();
    $html = view('trust-verification::admin.content.index', [
        'tab' => 'hub',
        'tabs' => ['hub', 'wizard', 'faq', 'legal', 'email', 'report', 'testimonials'],
        'blocks' => $blocks,
        'tvPermissions' => App\Plugins\TrustVerification\Services\TrustVerificationPermissionService::capabilities(),
    ])->render();
    echo 'OK length=' . strlen($html) . "\n";
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n";
    echo $e->getTraceAsString();
}
