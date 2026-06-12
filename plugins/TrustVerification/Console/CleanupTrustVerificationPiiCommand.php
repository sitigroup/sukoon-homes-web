<?php

namespace App\Plugins\TrustVerification\Console;

use App\Plugins\TrustVerification\Services\TrustVerificationPiiRetentionService;
use Illuminate\Console\Command;

class CleanupTrustVerificationPiiCommand extends Command
{
    protected $signature = 'trust-verification:cleanup-pii
                            {--dry-run : List files that would be deleted without changing data}';

    protected $description = 'Delete expired verification documents and reports per retention settings (keeps order records)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $settings = TrustVerificationPiiRetentionService::retentionSettings();

        $this->info($dryRun ? 'DRY RUN — no files or database records will be changed' : 'Running PII cleanup…');
        $this->line('Retention: documents='.$settings['document_retention_days'].'d, reports='.$settings['report_retention_days'].'d, cancelled unpaid='.$settings['delete_cancelled_unpaid_after_days'].'d');

        $stats = TrustVerificationPiiRetentionService::runCleanup($dryRun);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Cancelled unpaid orders processed', (string) $stats['cancelled_orders']],
                ['Documents '.($dryRun ? 'would be ' : '').'deleted', (string) $stats['documents_deleted']],
                ['Reports '.($dryRun ? 'would be ' : '').'deleted', (string) $stats['reports_deleted']],
                ['File paths', (string) count($stats['paths'])],
            ]
        );

        if ($stats['paths'] !== []) {
            $this->line('');
            $this->comment('Paths:');
            foreach (array_slice($stats['paths'], 0, 100) as $path) {
                $this->line('  '.$path);
            }
            if (count($stats['paths']) > 100) {
                $this->line('  … and '.(count($stats['paths']) - 100).' more');
            }
        }

        $this->info($dryRun ? 'Dry run complete.' : 'Cleanup complete.');

        return self::SUCCESS;
    }
}
