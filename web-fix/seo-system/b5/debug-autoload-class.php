<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$r = new ReflectionClass('App\Plugins\SeoEngine\Services\SeoEnginePageGeneratorService');
echo "loaded from: " . $r->getFileName() . "\n";
echo "has shouldGenerateCity: " . ($r->hasMethod('shouldGenerateCity') ? 'yes' : 'no') . "\n";
echo "has pruneStaleRentPages: " . ($r->hasMethod('pruneStaleRentPages') ? 'yes' : 'no') . "\n";
echo "has generatedPaths prop: " . ($r->hasProperty('generatedPaths') ? 'yes' : 'no') . "\n";
