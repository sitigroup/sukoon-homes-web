<?php

namespace App\Plugins\TrustVerification\Console;

use App\Plugins\TrustVerification\Services\TrustVerificationTrustBadgeService;
use Illuminate\Console\Command;

class RefreshTrustScoreCommand extends Command
{
    protected $signature = 'trust:refresh-score {customer_id? : Optional single customer ID}';

    protected $description = 'Recalculate Sukoon trust scores and auto-assign badges (queue-safe batch)';

    public function handle(): int
    {
        $customerId = $this->argument('customer_id');

        if ($customerId !== null) {
            $record = TrustVerificationTrustBadgeService::refreshScore((int) $customerId, 'cli');
            $this->info('Customer '.$customerId.' trust score: '.($record->trust_score ?? 0));

            return self::SUCCESS;
        }

        $count = TrustVerificationTrustBadgeService::bulkRefreshAll();
        $this->info('Refreshed trust scores for '.$count.' customer(s).');

        return self::SUCCESS;
    }
}
