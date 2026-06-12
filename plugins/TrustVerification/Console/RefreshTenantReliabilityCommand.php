<?php

namespace App\Plugins\TrustVerification\Console;

use App\Plugins\TrustVerification\Services\TrustVerificationTenantReliabilityService;
use Illuminate\Console\Command;

class RefreshTenantReliabilityCommand extends Command
{
    protected $signature = 'tenant-reliability:refresh {customer_id? : Optional tenant customer ID}';

    protected $description = 'Refresh owner-safe tenant reliability summaries (no fraud/risk exposure)';

    public function handle(): int
    {
        $customerId = $this->argument('customer_id');

        if ($customerId !== null) {
            $record = TrustVerificationTenantReliabilityService::refresh((int) $customerId, 'cli');
            if (! $record) {
                $this->warn('No completed tenant verification order for customer #'.$customerId);

                return self::SUCCESS;
            }
            $this->info(sprintf(
                'Customer #%s: %d%% — %s',
                $customerId,
                $record->verification_completion,
                TrustVerificationTenantReliabilityService::levelLabel($record->reliability_level)
            ));

            return self::SUCCESS;
        }

        $count = TrustVerificationTenantReliabilityService::bulkRefreshAll();
        $this->info('Tenant reliability refresh complete for '.$count.' customer(s).');

        return self::SUCCESS;
    }
}
