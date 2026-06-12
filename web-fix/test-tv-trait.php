<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$trait = 'App\Plugins\TrustVerification\Support\ChecksTrustVerificationPermissions';
echo trait_exists($trait) ? "TRAIT OK\n" : "TRAIT MISSING\n";
$ctrl = 'App\Plugins\TrustVerification\Http\Controllers\Admin\TrustVerificationAdminController';
try {
    echo class_exists($ctrl) ? "CTRL OK\n" : "CTRL FAIL\n";
} catch (Throwable $e) {
    echo 'CTRL ERROR: ' . $e->getMessage() . "\n";
}
