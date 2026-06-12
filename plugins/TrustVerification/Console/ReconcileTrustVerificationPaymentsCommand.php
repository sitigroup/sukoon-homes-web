<?php

namespace App\Plugins\TrustVerification\Console;

use App\Plugins\TrustVerification\Services\TrustVerificationPaymentService;
use Illuminate\Console\Command;

class ReconcileTrustVerificationPaymentsCommand extends Command
{
    protected $signature = 'trust-verification:reconcile-payments
                            {--dry-run : List orders that would be synced without updating}';

    protected $description = 'Sync tv_orders payment_status from linked payment_transactions (Phase D ops)';

    public function handle(): int
    {
        $result = TrustVerificationPaymentService::reconcilePendingPayments((bool) $this->option('dry-run'));

        $this->info($result['message']);

        return self::SUCCESS;
    }
}
