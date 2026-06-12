<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $blocks = App\Plugins\TrustVerification\Models\TvContentBlock::query()
        ->where('group_key', 'hub')
        ->count();
    echo "TvContentBlock count hub: {$blocks}\n";

    $controller = new App\Plugins\TrustVerification\Http\Controllers\Admin\TrustVerificationContentAdminController();
    $request = Illuminate\Http\Request::create('/trust-verification/content', 'GET');
    $response = $controller->index($request);
    echo 'Index response: ' . get_class($response) . "\n";
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n";
}
