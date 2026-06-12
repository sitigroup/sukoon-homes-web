<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$paths = [
    '/api/trust-verification/cities',
    '/api/trust-verification/packages',
    '/api/area-listing/states',
];

foreach ($paths as $path) {
    try {
        $r = app('router')->getRoutes()->match(
            Illuminate\Http\Request::create($path, 'GET')
        );
        echo "$path => {$r->uri()} [{$r->getActionName()}]\n";
    } catch (Throwable $e) {
        echo "$path => NO MATCH: {$e->getMessage()}\n";
    }
}
