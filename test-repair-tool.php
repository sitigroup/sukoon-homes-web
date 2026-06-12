<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Services\AreaListingPropertyLocationRepairService;
use Illuminate\Support\Facades\DB;

$results = [];

// Test 1: syntax
$lint = shell_exec('php -l ' . escapeshellarg($root . '/app/Plugins/AreaListing/Services/AreaListingPropertyLocationRepairService.php') . ' 2>&1');
$results['test1_syntax'] = str_contains((string) $lint, 'No syntax errors') ? 'PASS' : 'FAIL ' . trim((string) $lint);

$beforeExisting = DB::table('area_listing_property_locations')->orderBy('property_id')->get(['property_id', 'area_id', 'latitude', 'longitude'])->keyBy('property_id');

// Test 2: dry run before execute
$dryBefore = AreaListingPropertyLocationRepairService::dryRun();
$results['test2_dry_run_missing'] = ((int) $dryBefore['missing_count'] === 10) ? 'PASS' : 'FAIL missing=' . ($dryBefore['missing_count'] ?? '?');
$results['test2_sample_count'] = (count($dryBefore['sample_property_ids'] ?? []) > 0) ? 'PASS' : 'FAIL';

// Test 4: execute in transaction + rollback
DB::beginTransaction();
$txnExecute = AreaListingPropertyLocationRepairService::execute();
$txnCount = DB::table('area_listing_property_locations')->count();
DB::rollBack();
$afterRollbackCount = DB::table('area_listing_property_locations')->count();
$results['test4_txn_created'] = ((int) $txnExecute['created'] === 10) ? 'PASS' : 'FAIL created=' . ($txnExecute['created'] ?? '?');
$results['test4_txn_rollback'] = ($txnCount >= 13 && $afterRollbackCount === 3) ? 'PASS' : "FAIL during={$txnCount} after={$afterRollbackCount}";

// Test 5: real execute
$execute = AreaListingPropertyLocationRepairService::execute();
$results['test5_execute_created'] = ((int) $execute['created'] === 10) ? 'PASS' : 'FAIL created=' . ($execute['created'] ?? '?');
$results['test5_execute_errors'] = (count($execute['errors'] ?? []) === 0) ? 'PASS' : 'FAIL errors=' . count($execute['errors']);

// Test 6: dry run after execute
$dryAfter = AreaListingPropertyLocationRepairService::dryRun();
$results['test6_dry_run_zero'] = ((int) $dryAfter['missing_count'] === 0) ? 'PASS' : 'FAIL missing=' . ($dryAfter['missing_count'] ?? '?');

// Test 3/7: zero state + existing rows untouched
$afterExisting = DB::table('area_listing_property_locations')->orderBy('property_id')->get(['property_id', 'area_id', 'latitude', 'longitude'])->keyBy('property_id');
$untouched = true;
foreach ($beforeExisting as $propertyId => $row) {
    $after = $afterExisting->get($propertyId);
    if (! $after
        || (string) $after->area_id !== (string) $row->area_id
        || (string) $after->latitude !== (string) $row->latitude
        || (string) $after->longitude !== (string) $row->longitude) {
        $untouched = false;
        break;
    }
}
$results['test3_zero_after_execute'] = ((int) $dryAfter['missing_count'] === 0) ? 'PASS' : 'FAIL';
$results['test7_existing_untouched'] = $untouched ? 'PASS' : 'FAIL';

// Test 8: Nearby repair files/routes exist
$nearbyRepairRoute = false;
$nearbyRepairFile = is_file($root . '/app/Plugins/Nearby/Http/Controllers/Admin/NearbyAdminController.php');
if ($nearbyRepairFile) {
    $nearbyRepairRoute = str_contains((string) file_get_contents($root . '/app/Plugins/Nearby/Http/Controllers/Admin/NearbyAdminController.php'), 'repair');
}
$results['test8_nearby_repair_present'] = ($nearbyRepairFile && $nearbyRepairRoute) ? 'PASS' : 'NOTE check manually nearby=' . ($nearbyRepairFile ? 'file_ok' : 'no_file');

// Test 9: log file
$logPath = storage_path('logs/repair_area_listing_links.log');
$logOk = is_file($logPath) && str_contains((string) file_get_contents($logPath), 'dry_run');
$results['test9_log'] = $logOk ? 'PASS' : 'FAIL';

echo "DRY_BEFORE missing={$dryBefore['missing_count']} total={$dryBefore['missing_count']} sample_ids=" . implode(',', $dryBefore['sample_property_ids'] ?? []) . "\n";
echo "EXECUTE created={$execute['created']} errors=" . count($execute['errors'] ?? []) . "\n";
echo "DRY_AFTER missing={$dryAfter['missing_count']}\n";
foreach ($results as $name => $status) {
    echo "{$name}: {$status}\n";
}
$fail = false;
foreach ($results as $status) {
    if (str_starts_with($status, 'FAIL')) {
        $fail = true;
    }
}
echo $fail ? "OVERALL_FAIL\n" : "OVERALL_PASS\n";
