<?php

namespace App\Plugins\TrustVerification\Observers;

use App\Models\PaymentTransaction;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Services\TrustVerificationCashfreeWebhookService;

class PaymentTransactionObserver
{
    public function updated(PaymentTransaction $transaction): void
    {
        if (! $transaction->wasChanged('payment_status')) {
            return;
        }

        TvOrder::query()
            ->where('payment_transaction_id', $transaction->id)
            ->get()
            ->each(fn (TvOrder $order) => TrustVerificationCashfreeWebhookService::syncOrderFromObserver($order, $transaction));
    }
}
