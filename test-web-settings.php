<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $request = Illuminate\Http\Request::create('/api/web-settings', 'GET');
    $response = app()->handle($request);
    echo 'status=' . $response->getStatusCode() . PHP_EOL;
    echo substr($response->getContent(), 0, 200) . PHP_EOL;
} catch (Throwable $e) {
    echo 'FAIL: ' . $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
