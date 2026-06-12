<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$errors = new Illuminate\Support\ViewErrorBag();

try {
    $order = App\Plugins\TrustVerification\Models\TvOrder::with([
        'package', 'subject', 'checkItems', 'report', 'documents',
        'automationRuns', 'auditLogs', 'referenceContacts', 'policeVerification',
    ])->find(18);

    if (! $order) {
        echo "order 18 not found\n";
        exit(1);
    }

    echo 'hasPolice: '.(App\Plugins\TrustVerification\Services\TrustVerificationPoliceVerificationService::orderRequiresPoliceVerification($order) ? 'yes' : 'no')."\n";

    $html = view('trust-verification::admin.partials.police-verification-panel', [
        'hasPoliceVerification' => true,
        'order' => $order,
        'policeStatuses' => App\Plugins\TrustVerification\Services\TrustVerificationPoliceVerificationService::STATUS_LABELS,
        'policeVerificationTypes' => App\Plugins\TrustVerification\Services\TrustVerificationPoliceVerificationService::VERIFICATION_TYPES,
        'rajasthanStatusUrl' => App\Plugins\TrustVerification\Services\TrustVerificationPoliceVerificationService::RAJASTHAN_STATUS_CHECK_URL,
        'cityLabel' => App\Plugins\TrustVerification\Services\TrustVerificationService::cityLabel($order->city_slug),
        'tvPermissions' => ['update' => true, 'documents' => true, 'reports' => true],
    ])->render();

    echo 'panel ok len='.strlen($html)."\n";
} catch (Throwable $e) {
    echo 'ERR: '.$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
}
