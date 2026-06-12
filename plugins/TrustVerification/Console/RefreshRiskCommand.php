<?php

namespace App\Plugins\TrustVerification\Console;

use App\Plugins\TrustVerification\Services\TrustVerificationFraudRiskService;
use Illuminate\Console\Command;

class RefreshRiskCommand extends Command
{
    protected $signature = 'risk:refresh {customer_id? : Optional customer ID to refresh}';

    protected $description = 'Refresh fraud risk signals and profiles (admin-only data)';

    public function handle(): int
    {
        $customerId = $this->argument('customer_id');

        if ($customerId !== null) {
            $profile = TrustVerificationFraudRiskService::refreshCustomer((int) $customerId, 'cli');
            $this->info('Risk refreshed for customer #'.$customerId.' — score '.$profile->risk_score.' ('.$profile->risk_level.')');

            return self::SUCCESS;
        }

        $count = TrustVerificationFraudRiskService::bulkRefreshAll();
        $this->info('Risk refresh complete for '.$count.' customer(s).');

        return self::SUCCESS;
    }
}
