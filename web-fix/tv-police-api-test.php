<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $order = App\Plugins\TrustVerification\Models\TvOrder::with([
        'package', 'subject', 'checkItems', 'report', 'documents',
        'automationRuns', 'referenceContacts', 'policeVerification',
    ])->find(18);

    App\Plugins\TrustVerification\Services\TrustVerificationPoliceVerificationService::summaryForCustomer($order);
    App\Plugins\TrustVerification\Services\TrustVerificationService::formatOrder($order, true);
    echo "formatOrder ok\n";
} catch (Throwable $e) {
    echo 'ERR: '.$e->getMessage()."\n".$e->getFile().':'.$e->getLine()."\n";
}
