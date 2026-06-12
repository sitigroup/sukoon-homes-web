<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$class = 'App\Plugins\TrustVerification\Services\Automation\ManualVerificationProvider';
echo class_exists($class) ? "OK: $class\n" : "MISSING: $class\n";
