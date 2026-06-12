<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Models\City;
use App\Plugins\SeoEngine\Services\SeoEnginePageGeneratorService;
use App\Plugins\SeoEngine\Services\SeoEnginePageDataService;

$gen = app(SeoEnginePageGeneratorService::class);
$ref = new ReflectionClass($gen);
$m = $ref->getMethod('shouldGenerateCity');
$m->setAccessible(true);
$cities = City::query()->where('status', true)->orderBy('name')->get();
foreach ($cities as $c) {
    echo "shouldGenerateCity({$c->id} {$c->slug}) = " . ($m->invoke($gen, $c, $cities) ? 'true' : 'false') . "\n";
}

app(SeoEnginePageDataService::class)->bustAllPageCaches();
$stats = $gen->generate();
echo "\nstats: " . json_encode($stats) . "\n";

$payload = app(SeoEnginePageDataService::class)->getByPath('/rent/barmer/');
foreach ($payload['listings'] ?? [] as $l) {
    echo "listing {$l['id']}: city={$l['city']}\n";
}
