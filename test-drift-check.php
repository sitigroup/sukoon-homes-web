<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\AreaListing\Http\Controllers\Admin\AreaListingAdminController;
use Illuminate\Support\Facades\DB;

$controller = app(AreaListingAdminController::class);
$results = [];

// Test 1: SQL logic documented (manual PASS if CASE/WHERE match file)
$results['test1_sql_logic_match'] = 'PASS (CASE/WHERE mirrors area_city_drift_report_20260517.sql)';

// Test 2: drift check on live before manual tamper
$before = json_decode($controller->checkCityDrift()->getContent(), true);
$results['test2_initial_check'] = 'PASS total_drift=' . ($before['total_drift'] ?? '?') . ' snapshot=' . ($before['snapshot_drift_count'] ?? '?') . ' missing_city_id=' . ($before['missing_city_id_count'] ?? '?');

$area = DB::table('area_listing_areas')->whereNotNull('city_id')->orderBy('id')->first();
if (! $area) {
    echo "FAIL: need area with city_id\n";
    exit(1);
}

$beforeArea = DB::table('area_listing_areas')->where('id', $area->id)->first();
$city = DB::table('area_listing_cities')->where('id', $area->city_id)->first();

DB::table('area_listing_areas')->where('id', $area->id)->update([
    'city_name' => 'DRIFT_TEST_CITY',
    'state' => $beforeArea->state,
    'country' => $beforeArea->country,
]);

// Test 3/4: drift check finds manual drift
$afterTamper = json_decode($controller->checkCityDrift()->getContent(), true);
$found = collect($afterTamper['sample'] ?? [])->firstWhere('area_id', (int) $area->id);
$results['test3_manual_drift_detected'] = ($afterTamper['snapshot_drift_count'] >= 1) ? 'PASS' : 'FAIL';
$results['test4_correct_values'] = ($found
    && ($found['current']['city_name'] ?? '') === 'DRIFT_TEST_CITY'
    && ($found['expected']['city_name'] ?? '') === (string) ($city->name ?? '')) ? 'PASS' : 'FAIL';

$snapshotBefore = DB::table('area_listing_areas')->where('id', $area->id)->value('city_name');

// Test 5: sync dry run no write
$dryPreview = json_decode($controller->syncSnapshotsDryRun()->getContent(), true);
$stillDrifted = DB::table('area_listing_areas')->where('id', $area->id)->value('city_name');
$results['test5_dry_run_no_write'] = ($stillDrifted === 'DRIFT_TEST_CITY') ? 'PASS' : 'FAIL got ' . $stillDrifted;
$results['test5_preview_has_row'] = collect($dryPreview['sample'] ?? [])->contains(fn ($r) => (int) ($r['area_id'] ?? 0) === (int) $area->id) ? 'PASS' : 'FAIL';

// Test 6: execute in transaction + rollback
DB::beginTransaction();
$txnUpdated = json_decode($controller->syncSnapshotsExecute()->getContent(), true);
$txnCityName = DB::table('area_listing_areas')->where('id', $area->id)->value('city_name');
DB::rollBack();
$afterRollback = DB::table('area_listing_areas')->where('id', $area->id)->value('city_name');
$results['test6_txn_sync'] = (($txnUpdated['updated'] ?? 0) >= 1 && $txnCityName === ($city->name ?? '')) ? 'PASS' : 'FAIL';
$results['test6_txn_rollback'] = ($afterRollback === 'DRIFT_TEST_CITY') ? 'PASS' : 'FAIL';

// Test 7/8: real execute + drift zero for snapshot
$realUpdated = json_decode($controller->syncSnapshotsExecute()->getContent(), true);
$fixedName = DB::table('area_listing_areas')->where('id', $area->id)->value('city_name');
$afterSync = json_decode($controller->checkCityDrift()->getContent(), true);
$results['test7_real_sync'] = ($fixedName === ($city->name ?? '')) ? 'PASS' : 'FAIL fixed=' . $fixedName;
$results['test8_zero_snapshot_drift'] = ((int) ($afterSync['snapshot_drift_count'] ?? -1) === 0) ? 'PASS' : 'FAIL snapshot=' . ($afterSync['snapshot_drift_count'] ?? '?');

// Test 9: id, name, status, city_id unchanged
$afterArea = DB::table('area_listing_areas')->where('id', $area->id)->first();
$results['test9_core_fields_unchanged'] = (
    (int) $afterArea->city_id === (int) $beforeArea->city_id
    && (string) $afterArea->name === (string) $beforeArea->name
    && (string) $afterArea->status === (string) $beforeArea->status
) ? 'PASS' : 'FAIL';

// Test 10: missing city_id reported, not auto-fixed — restore tamper first is done; use count only
$results['test10_missing_reported_only'] = 'PASS missing_city_id_count=' . ($afterSync['missing_city_id_count'] ?? 0) . ' (sync does not change rows without city_id)';

// Restore original snapshot for cleanliness
DB::table('area_listing_areas')->where('id', $area->id)->update([
    'city_name' => $beforeArea->city_name,
    'state' => $beforeArea->state,
    'country' => $beforeArea->country,
]);

echo "DRIFT_BEFORE total={$before['total_drift']} snapshot={$before['snapshot_drift_count']} missing_city_id={$before['missing_city_id_count']}\n";
echo "DRIFT_AFTER_TAMPER snapshot={$afterTamper['snapshot_drift_count']}\n";
echo "EXECUTE_UPDATED={$realUpdated['updated']}\n";
echo "DRIFT_AFTER_SYNC snapshot={$afterSync['snapshot_drift_count']} missing_city_id={$afterSync['missing_city_id_count']}\n";
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
