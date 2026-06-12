<?php

namespace App\Plugins\TrustVerification\Http\Controllers\Api;

use App\Plugins\TrustVerification\Services\TrustVerificationCashfreeWebhookService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TrustVerificationCashfreeWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        return TrustVerificationCashfreeWebhookService::handleHttpWebhook($request);
    }
}
