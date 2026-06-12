<?php
/**
 * Migrate Trust Verification report PDFs from public disk to local (private) disk.
 *
 * Run on admin server from Laravel root:
 *   php web-fix/migrate-trust-verification-reports.php           # dry-run (default)
 *   php web-fix/migrate-trust-verification-reports.php --execute
 *   php web-fix/migrate-trust-verification-reports.php --execute --delete-public
 *
 * Safe to re-run: files already on local disk are skipped.
 */

$base = dirname(__DIR__);
if (! is_file($base.'/vendor/autoload.php')) {
    $base = '/www/wwwroot/admin-homes';
}

require $base.'/vendor/autoload.php';
$app = require_once $base.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Plugins\TrustVerification\Services\TrustVerificationService;

$execute = in_array('--execute', $argv ?? [], true);
$deletePublic = in_array('--delete-public', $argv ?? [], true);
$dryRun = ! $execute;

echo "Trust Verification — legacy report PDF migration\n";
echo 'Mode: '.($dryRun ? 'DRY RUN (no files copied)' : 'EXECUTE')."\n";
if ($deletePublic && ! $dryRun) {
    echo "Will delete public copies after successful copy.\n";
}
echo str_repeat('-', 50)."\n";

$stats = TrustVerificationService::migrateLegacyReportFiles($dryRun, $deletePublic);

echo "Scanned:  {$stats['scanned']}\n";
echo "Migrated: {$stats['migrated']}\n";
echo "Skipped:  {$stats['skipped']} (already on local disk)\n";
echo "Missing:  {$stats['missing']} (not on public or local)\n";

if (! empty($stats['errors'])) {
    echo "\nErrors:\n";
    foreach ($stats['errors'] as $reportId => $message) {
        echo "  Report #{$reportId}: {$message}\n";
    }
    exit(1);
}

if ($dryRun && $stats['migrated'] > 0) {
    echo "\nRe-run with --execute to copy {$stats['migrated']} file(s).\n";
}

echo "\nDone.\n";
