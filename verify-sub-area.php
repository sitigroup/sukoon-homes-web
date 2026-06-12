<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$areaId = App\Plugins\AreaListing\Models\Area::query()->active()->orderBy('id')->value('id');
$path = '/api/area-listing/sub-areas?area_id=' . $areaId;
$request = Illuminate\Http\Request::create($path, 'GET');
$request->headers->set('Accept', 'application/json');
$response = $kernel->handle($request);
$kernel->terminate($request, $response);
echo "area_id={$areaId} status={$response->getStatusCode()}\n";
echo ($response->getStatusCode() === 200 ? '8_sub: PASS' : '8_sub: FAIL') . "\n";
