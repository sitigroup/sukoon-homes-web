<?php

use App\Plugins\TrustVerification\Http\Controllers\Api\TrustVerificationApiController;
use App\Plugins\TrustVerification\Http\Controllers\Api\TrustVerificationCashfreeWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('trust-verification')->group(function () {
    Route::get('packages', [TrustVerificationApiController::class, 'packages']);
    Route::get('cities', [TrustVerificationApiController::class, 'cities']);
    Route::get('payment-settings', [TrustVerificationApiController::class, 'paymentSettings']);
    Route::get('settings', [TrustVerificationApiController::class, 'documentSettings']);
    Route::get('content/public', [TrustVerificationApiController::class, 'publicContent']);
    Route::get('public-trust/{customerId}', [TrustVerificationApiController::class, 'publicTrust'])
        ->where('customerId', '[0-9]+');
    Route::get('tenant-reliability/{customerId}', [TrustVerificationApiController::class, 'publicTenantReliability'])
        ->where('customerId', '[0-9]+');

    Route::get('sample-reports/public', [TrustVerificationApiController::class, 'publicSampleReport']);
    Route::get('sample-reports/{sampleReport}/download', [TrustVerificationApiController::class, 'downloadSampleReport'])
        ->middleware('tv.throttle:sample-report-download');
    Route::post('sample-reports/{sampleReport}/viewed', [TrustVerificationApiController::class, 'sampleReportViewed'])
        ->middleware('tv.throttle:sample-report-view');

    Route::post('webhook/automation', [TrustVerificationApiController::class, 'automationWebhook'])
        ->middleware('tv.throttle:webhook-automation');
    Route::post('webhook/cashfree', TrustVerificationCashfreeWebhookController::class)
        ->middleware('tv.throttle:webhook-cashfree');

    Route::get('reports/{order}/download', [TrustVerificationApiController::class, 'emailDownloadReport'])
        ->middleware('signed')
        ->name('trust-verification.reports.email-download');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('orders', [TrustVerificationApiController::class, 'orders']);
        Route::post('orders', [TrustVerificationApiController::class, 'storeOrder'])
            ->middleware('tv.throttle:order-create');
        Route::get('orders/{order}', [TrustVerificationApiController::class, 'showOrder']);
        Route::get('orders/{order}/report/download', [TrustVerificationApiController::class, 'downloadReport']);

        Route::post('orders/{order}/documents', [TrustVerificationApiController::class, 'uploadDocuments'])
            ->middleware('tv.throttle:document-upload');
        Route::get('orders/{order}/documents/{document}/download', [TrustVerificationApiController::class, 'downloadDocument']);

        Route::post('orders/{order}/payment-intent', [TrustVerificationApiController::class, 'createPaymentIntent'])
            ->middleware('tv.throttle:payment-intent');
        Route::post('orders/{order}/confirm-payment', [TrustVerificationApiController::class, 'confirmPayment'])
            ->middleware('tv.throttle:confirm-payment');

        Route::post('orders/{order}/cancel', [TrustVerificationApiController::class, 'cancelOrder']);

        Route::get('orders/{order}/references', [TrustVerificationApiController::class, 'listReferences']);
        Route::post('orders/{order}/references', [TrustVerificationApiController::class, 'storeReferences'])
            ->middleware('tv.throttle:order-create');

        Route::get('orders/{order}/police-verification', [TrustVerificationApiController::class, 'showPoliceVerification']);
        Route::post('orders/{order}/police-verification', [TrustVerificationApiController::class, 'storePoliceVerification'])
            ->middleware('tv.throttle:order-create');
        Route::post('orders/{order}/police-verification/acknowledgement', [TrustVerificationApiController::class, 'uploadPoliceAcknowledgement'])
            ->middleware('tv.throttle:document-upload');
        Route::get('orders/{order}/police-verification/acknowledgement/download', [TrustVerificationApiController::class, 'downloadPoliceAcknowledgement']);
        Route::get('orders/{order}/police-verification/certificate/download', [TrustVerificationApiController::class, 'downloadPoliceCertificate']);

        Route::get('trust-profile', [TrustVerificationApiController::class, 'trustProfile']);
        Route::get('verification-badges', [TrustVerificationApiController::class, 'myVerificationBadges']);
        Route::get('verification-badges/{verificationBadge}/download', [TrustVerificationApiController::class, 'downloadVerificationBadge'])
            ->where('verificationBadge', '[0-9]+');
        Route::get('orders/{order}/verification-badge', [TrustVerificationApiController::class, 'orderVerificationBadge']);
    });
});
