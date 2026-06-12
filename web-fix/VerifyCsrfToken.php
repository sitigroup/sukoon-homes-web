<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Http\Request;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/webhook/razorpay',
        '/webhook/paystack',
        '/webhook/paypal',
        '/webhook/stripe',
        '/webhook/flutterwave',
        '/webhook/cashfree',
        '/webhook/phonepe',
        '/webhook/midtrans',
        '/InApp/Appstore',
        '/firebase_messaging_settings',
        'area-listing/repair-locations/dry-run',
        'area-listing/repair-locations/execute',
        'area-listing/drift-check',
        'area-listing/drift-check/*',
    ];

    /**
     * Allow Bearer-authenticated API-style POSTs to admin maintenance endpoints.
     */
    protected function inExceptArray($request)
    {
        if ($this->isAreaListingMaintenanceRequest($request)
            && ($request->bearerToken() || $request->query('token') || $request->query('api_token'))) {
            return true;
        }

        return parent::inExceptArray($request);
    }

    protected function isAreaListingMaintenanceRequest(Request $request): bool
    {
        return $request->is(
            'area-listing/repair-locations/*',
            'area-listing/drift-check',
            'area-listing/drift-check/*'
        );
    }
}
