<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $controller = app(App\Plugins\TrustVerification\Http\Controllers\Api\TrustVerificationApiController::class);
    $request = Illuminate\Http\Request::create('/api/trust-verification/packages?type=tenant&city=barmer', 'GET');
    $response = $controller->packages($request);
    echo $response->getContent() . PHP_EOL;
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
