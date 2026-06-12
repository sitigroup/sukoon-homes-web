<?php



use App\Plugins\TrustVerification\Http\Controllers\Admin\TrustVerificationAdminController;

use App\Plugins\TrustVerification\Http\Controllers\Admin\TrustVerificationAutomationAdminController;

use App\Plugins\TrustVerification\Http\Controllers\Admin\TrustVerificationCityAdminController;

use App\Plugins\TrustVerification\Http\Controllers\Admin\TrustVerificationPackageAdminController;

use App\Plugins\TrustVerification\Http\Controllers\Admin\TrustVerificationContentAdminController;

use App\Plugins\TrustVerification\Http\Controllers\Admin\TrustVerificationSampleReportAdminController;
use App\Plugins\TrustVerification\Http\Controllers\Admin\TrustVerificationIssuedBadgeAdminController;
use App\Plugins\TrustVerification\Http\Controllers\Admin\TrustVerificationTrustAdminController;
use App\Plugins\TrustVerification\Http\Controllers\Admin\TrustVerificationRiskAdminController;
use App\Plugins\TrustVerification\Http\Controllers\Admin\TrustVerificationTenantReliabilityAdminController;

use Illuminate\Support\Facades\Route;



Route::prefix('trust-verification')->name('trust-verification.')->group(function () {

    Route::get('/', [TrustVerificationAdminController::class, 'index'])->name('index');

    Route::get('orders/{order}', [TrustVerificationAdminController::class, 'show'])->name('orders.show');

    Route::post('orders/{order}/status', [TrustVerificationAdminController::class, 'updateStatus'])->name('orders.status');

    Route::post('orders/{order}/checks', [TrustVerificationAdminController::class, 'updateChecks'])->name('orders.checks');

    Route::post('orders/{order}/references', [TrustVerificationAdminController::class, 'storeReference'])->name('orders.references.store');
    Route::post('orders/{order}/references/{reference}', [TrustVerificationAdminController::class, 'updateReference'])->name('orders.references.update');

    Route::post('orders/{order}/police-verification', [TrustVerificationAdminController::class, 'updatePoliceVerification'])->name('orders.police.update');
    Route::get('orders/{order}/police-verification', [TrustVerificationAdminController::class, 'redirectPoliceVerification'])->name('orders.police.show');
    Route::post('orders/{order}/police-verification/status', [TrustVerificationAdminController::class, 'transitionPoliceStatus'])->name('orders.police.status');
    Route::get('orders/{order}/police-verification/acknowledgement/download', [TrustVerificationAdminController::class, 'downloadPoliceAcknowledgement'])->name('orders.police.acknowledgement.download');
    Route::get('orders/{order}/police-verification/certificate/download', [TrustVerificationAdminController::class, 'downloadPoliceCertificate'])->name('orders.police.certificate.download');

    Route::post('orders/{order}/report', [TrustVerificationAdminController::class, 'uploadReport'])->name('orders.report');

    Route::get('orders/{order}/report/download', [TrustVerificationAdminController::class, 'downloadReport'])->name('orders.report.download');

    Route::post('orders/{order}/payment', [TrustVerificationAdminController::class, 'markPayment'])->name('orders.payment');

    Route::post('orders/{order}/automation/run', [TrustVerificationAdminController::class, 'runAutomation'])->name('orders.automation.run');

    Route::get('orders/{order}/documents/{document}/download', [TrustVerificationAdminController::class, 'downloadDocument'])->name('orders.documents.download');

    Route::post('orders/{order}/documents/delete', [TrustVerificationAdminController::class, 'deleteDocuments'])->name('orders.documents.delete');

    Route::post('orders/{order}/report/delete', [TrustVerificationAdminController::class, 'deleteReport'])->name('orders.report.delete');



    Route::get('packages', [TrustVerificationPackageAdminController::class, 'index'])->name('packages.index');

    Route::get('packages/create', [TrustVerificationPackageAdminController::class, 'create'])->name('packages.create');

    Route::post('packages', [TrustVerificationPackageAdminController::class, 'store'])->name('packages.store');

    Route::get('packages/{package}/edit', [TrustVerificationPackageAdminController::class, 'edit'])->name('packages.edit');

    Route::put('packages/{package}', [TrustVerificationPackageAdminController::class, 'update'])->name('packages.update');



    Route::get('cities', [TrustVerificationCityAdminController::class, 'index'])->name('cities.index');

    Route::post('cities', [TrustVerificationCityAdminController::class, 'store'])->name('cities.store');

    Route::put('cities/{city}', [TrustVerificationCityAdminController::class, 'update'])->name('cities.update');

    Route::post('cities/{city}/toggle', [TrustVerificationCityAdminController::class, 'toggle'])->name('cities.toggle');



    Route::get('automation', [TrustVerificationAutomationAdminController::class, 'index'])->name('automation.index');

    Route::put('automation', [TrustVerificationAutomationAdminController::class, 'update'])->name('automation.update');

    Route::post('automation/reconcile-payments', [TrustVerificationAutomationAdminController::class, 'reconcilePayments'])->name('automation.reconcile');

    Route::get('content', [TrustVerificationContentAdminController::class, 'index'])->name('content.index');
    Route::post('content/seed', [TrustVerificationContentAdminController::class, 'seed'])->name('content.seed');
    Route::post('content/faq', [TrustVerificationContentAdminController::class, 'storeFaq'])->name('content.faq.store');
    Route::post('content/reorder', [TrustVerificationContentAdminController::class, 'reorder'])->name('content.reorder');
    Route::get('content/blocks/{block}/edit', [TrustVerificationContentAdminController::class, 'edit'])->name('content.edit');
    Route::put('content/blocks/{block}', [TrustVerificationContentAdminController::class, 'update'])->name('content.update');
    Route::post('content/blocks/{block}/publish', [TrustVerificationContentAdminController::class, 'togglePublish'])->name('content.publish');
    Route::post('content/blocks/{block}/reset', [TrustVerificationContentAdminController::class, 'reset'])->name('content.reset');

    Route::get('sample-reports', [TrustVerificationSampleReportAdminController::class, 'index'])->name('sample-reports.index');
    Route::get('sample-reports/create', [TrustVerificationSampleReportAdminController::class, 'create'])->name('sample-reports.create');
    Route::post('sample-reports', [TrustVerificationSampleReportAdminController::class, 'store'])->name('sample-reports.store');
    Route::get('sample-reports/{sampleReport}/edit', [TrustVerificationSampleReportAdminController::class, 'edit'])->name('sample-reports.edit');
    Route::put('sample-reports/{sampleReport}', [TrustVerificationSampleReportAdminController::class, 'update'])->name('sample-reports.update');
    Route::post('sample-reports/{sampleReport}/delete', [TrustVerificationSampleReportAdminController::class, 'destroy'])->name('sample-reports.destroy');
    Route::get('sample-reports/{sampleReport}/download', [TrustVerificationSampleReportAdminController::class, 'download'])->name('sample-reports.download');

    Route::prefix('reliability')->name('reliability.')->group(function () {
        Route::get('/', [TrustVerificationTenantReliabilityAdminController::class, 'index'])->name('index');
        Route::post('bulk-refresh', [TrustVerificationTenantReliabilityAdminController::class, 'bulkRefresh'])->name('bulk-refresh');
        Route::get('{customerId}', [TrustVerificationTenantReliabilityAdminController::class, 'show'])->name('show')->where('customerId', '[0-9]+');
        Route::post('{customerId}/refresh', [TrustVerificationTenantReliabilityAdminController::class, 'refreshCustomer'])->name('refresh')->where('customerId', '[0-9]+');
        Route::post('{customerId}/public', [TrustVerificationTenantReliabilityAdminController::class, 'togglePublic'])->name('public')->where('customerId', '[0-9]+');
    });

    Route::prefix('risk')->name('risk.')->group(function () {
        Route::get('profiles', [TrustVerificationRiskAdminController::class, 'profilesIndex'])->name('profiles.index');
        Route::post('profiles/bulk-refresh', [TrustVerificationRiskAdminController::class, 'bulkRefresh'])->name('profiles.bulk-refresh');
        Route::get('profiles/{customerId}', [TrustVerificationRiskAdminController::class, 'profilesShow'])->name('profiles.show')->where('customerId', '[0-9]+');
        Route::get('profiles/{customerId}/api', [TrustVerificationRiskAdminController::class, 'profileApi'])->name('profiles.api')->where('customerId', '[0-9]+');
        Route::post('profiles/{customerId}/refresh', [TrustVerificationRiskAdminController::class, 'refreshCustomer'])->name('profiles.refresh')->where('customerId', '[0-9]+');
        Route::post('profiles/{customerId}/override', [TrustVerificationRiskAdminController::class, 'manualOverride'])->name('profiles.override')->where('customerId', '[0-9]+');
        Route::post('profiles/{customerId}/manual-flag', [TrustVerificationRiskAdminController::class, 'storeManualFlag'])->name('profiles.manual-flag')->where('customerId', '[0-9]+');
        Route::get('signals', [TrustVerificationRiskAdminController::class, 'signalsIndex'])->name('signals.index');
        Route::delete('signals/{signal}', [TrustVerificationRiskAdminController::class, 'destroySignal'])->name('signals.destroy');
    });

    Route::prefix('verification-badges')->name('verification-badges.')->group(function () {
        Route::get('/', [TrustVerificationIssuedBadgeAdminController::class, 'index'])->name('index');
        Route::get('{verificationBadge}', [TrustVerificationIssuedBadgeAdminController::class, 'show'])->name('show');
        Route::post('orders/{order}/issue', [TrustVerificationIssuedBadgeAdminController::class, 'issue'])->name('issue');
        Route::post('orders/{order}/issue-owner', [TrustVerificationIssuedBadgeAdminController::class, 'issueOwner'])->name('issue-owner');
        Route::post('orders/{order}/issue-verified-owner', [TrustVerificationIssuedBadgeAdminController::class, 'issueVerifiedOwner'])->name('issue-verified-owner');
        Route::post('{verificationBadge}/verify', [TrustVerificationIssuedBadgeAdminController::class, 'verify'])->name('verify');
        Route::post('{verificationBadge}/revoke', [TrustVerificationIssuedBadgeAdminController::class, 'revoke'])->name('revoke');
        Route::post('{verificationBadge}/renew', [TrustVerificationIssuedBadgeAdminController::class, 'renew'])->name('renew');
        Route::get('{verificationBadge}/download', [TrustVerificationIssuedBadgeAdminController::class, 'downloadCertificate'])->name('download');
    });

    Route::prefix('trust')->name('trust.')->group(function () {
        Route::get('badges', [TrustVerificationTrustAdminController::class, 'badgesIndex'])->name('badges.index');
        Route::get('badges/{badge}/edit', [TrustVerificationTrustAdminController::class, 'badgesEdit'])->name('badges.edit');
        Route::put('badges/{badge}', [TrustVerificationTrustAdminController::class, 'badgesUpdate'])->name('badges.update');
        Route::get('rules', [TrustVerificationTrustAdminController::class, 'rulesIndex'])->name('rules.index');
        Route::post('rules', [TrustVerificationTrustAdminController::class, 'rulesUpdate'])->name('rules.update');
        Route::get('customers', [TrustVerificationTrustAdminController::class, 'customersIndex'])->name('customers.index');
        Route::post('customers/bulk-refresh', [TrustVerificationTrustAdminController::class, 'bulkRefresh'])->name('customers.bulk-refresh');
        Route::get('customers/{customerId}', [TrustVerificationTrustAdminController::class, 'customersShow'])->name('customers.show')->where('customerId', '[0-9]+');
        Route::post('customers/{customerId}/recalculate', [TrustVerificationTrustAdminController::class, 'recalculateCustomer'])->name('customers.recalculate')->where('customerId', '[0-9]+');
        Route::post('customers/{customerId}/adjustment', [TrustVerificationTrustAdminController::class, 'manualAdjustment'])->name('customers.adjustment')->where('customerId', '[0-9]+');
        Route::post('customers/{customerId}/public', [TrustVerificationTrustAdminController::class, 'togglePublic'])->name('customers.public')->where('customerId', '[0-9]+');
        Route::post('customers/{customerId}/assign-badge', [TrustVerificationTrustAdminController::class, 'assignBadge'])->name('customers.assign-badge')->where('customerId', '[0-9]+');
        Route::post('customers/{customerId}/badges/{customerBadge}/revoke', [TrustVerificationTrustAdminController::class, 'revokeBadge'])->name('customers.revoke-badge')->where('customerId', '[0-9]+');
    });

});
