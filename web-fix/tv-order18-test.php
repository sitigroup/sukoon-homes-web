<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $order = App\Plugins\TrustVerification\Models\TvOrder::with([
        'package', 'subject', 'checkItems', 'report', 'documents',
        'automationRuns', 'auditLogs', 'referenceContacts',
    ])->find(18);

    if (! $order) {
        echo "order 18 not found\n";
        exit(1);
    }

    echo 'order: '.$order->id.PHP_EOL;
    echo 'refs: '.$order->referenceContacts->count().PHP_EOL;
    echo 'hasRef: '.(App\Plugins\TrustVerification\Services\TrustVerificationReferenceService::orderHasReferenceCheck($order) ? 'yes' : 'no').PHP_EOL;
    echo 'progress: '.json_encode(App\Plugins\TrustVerification\Services\TrustVerificationReferenceService::progressForOrder($order)).PHP_EOL;

    App\Plugins\TrustVerification\Services\TrustVerificationService::formatOrder($order, true);
    echo "formatOrder ok\n";

    App\Plugins\TrustVerification\Services\TrustVerificationOrderTimestampsService::resolve($order);
    echo "timestamps ok\n";

    $errors = new Illuminate\Support\ViewErrorBag();

    $html = view('trust-verification::admin.show', [
        'order' => $order,
        'formatted' => App\Plugins\TrustVerification\Services\TrustVerificationService::formatOrder($order, true),
        'orderTimestamps' => App\Plugins\TrustVerification\Services\TrustVerificationOrderTimestampsService::resolve($order),
        'checkStatuses' => ['pending', 'pass', 'fail', 'na'],
        'riskLevels' => ['green', 'amber', 'red'],
        'checkSummary' => ['total' => 0, 'pass' => 0, 'fail' => 0, 'pending' => 0, 'na' => 0],
        'dueAt' => null,
        'cityLabel' => App\Plugins\TrustVerification\Services\TrustVerificationService::cityLabel($order->city_slug),
        'hasReportFile' => false,
        'reportMissingForCompleted' => false,
        'hasActiveDocuments' => false,
        'riskSuggestion' => App\Plugins\TrustVerification\Services\TrustVerificationRiskService::suggestRiskLevel($order),
        'riskSummaryDraft' => App\Plugins\TrustVerification\Services\TrustVerificationAutomationService::generateRiskSummary($order),
        'automationSettings' => App\Plugins\TrustVerification\Services\TrustVerificationSettingsService::automationPayload(),
        'documentTypes' => App\Plugins\TrustVerification\Services\TrustVerificationSettingsService::DOCUMENT_TYPES,
        'auditLogs' => collect(),
        'referenceTypes' => App\Plugins\TrustVerification\Services\TrustVerificationReferenceService::TYPE_LABELS,
        'referenceStatuses' => App\Plugins\TrustVerification\Services\TrustVerificationReferenceService::STATUS_LABELS,
        'referenceProgress' => App\Plugins\TrustVerification\Services\TrustVerificationReferenceService::progressForOrder($order),
        'hasReferenceCheck' => App\Plugins\TrustVerification\Services\TrustVerificationReferenceService::orderHasReferenceCheck($order),
        'tvPermissions' => ['read' => true, 'update' => true, 'audit' => true, 'reports' => true, 'documents' => true, 'payments' => true],
        'errors' => $errors,
    ])->render();

    echo 'view render(df='.strlen($html).")\n";
} catch (Throwable $e) {
    echo 'ERR: '.$e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n";
    echo $e->getTraceAsString()."\n";
    exit(1);
}
