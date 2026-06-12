<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvVerificationBadge;
use App\Plugins\TrustVerification\Services\TrustVerificationIssuedBadgeService;
use App\Plugins\TrustVerification\Services\TrustVerificationTrustBadgeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== Check 1: tv_verification_badges (issued SVO/SVT) ===\n";
if (! Schema::hasTable('tv_verification_badges')) {
    echo "TABLE MISSING\n";
} else {
    $rows = DB::table('tv_verification_badges')->orderByDesc('id')->limit(20)->get();
    echo 'count=' . DB::table('tv_verification_badges')->count() . "\n";
    foreach ($rows as $r) {
        echo "{$r->id} cust={$r->customer_id} order={$r->order_id} type={$r->badge_type} num={$r->badge_number} status={$r->status}\n";
    }
}

echo "\n=== Check 1b: tv_trust_badges (score catalog) ===\n";
echo 'catalog_badges=' . DB::table('tv_trust_badges')->count() . "\n";

echo "\n=== Check 2: completed orders ===\n";
$orders = TvOrder::query()
    ->where('status', 'completed')
    ->orderByDesc('id')
    ->limit(20)
    ->get(['id', 'order_number', 'customer_id', 'order_type', 'status', 'payment_status']);
foreach ($orders as $o) {
    $hasBadge = Schema::hasTable('tv_verification_badges')
        ? (DB::table('tv_verification_badges')->where('order_id', $o->id)->exists() ? 'Y' : 'N')
        : '?';
    $eligible = TrustVerificationIssuedBadgeService::isEligible($o) ? 'eligible' : 'not-eligible';
    echo "#{$o->id} {$o->order_number} cust={$o->customer_id} type={$o->order_type} pay={$o->payment_status} badge={$hasBadge} {$eligible}\n";
}

echo "\n=== Check 3: public API sample customers ===\n";
$customerIds = $orders->pluck('customer_id')->unique()->filter()->take(5);
foreach ($customerIds as $cid) {
    $p = TrustVerificationTrustBadgeService::formatForApi((int) $cid, true);
    if (! $p) {
        echo "customer {$cid}: no public trust payload\n";
        continue;
    }
    echo "customer {$cid}: owner=" . ($p['owner_trust_badge'] ? 'true' : 'false');
    echo ' tenant=' . ($p['tenant_trust_badge'] ? 'true' : 'false');
    echo ' svo=' . ($p['sukoon_verified_owner'] ? 'yes' : 'no');
    echo ' catalog=' . implode(',', array_column($p['badges'] ?? [], 'slug')) . "\n";
}

$ownerOrder = TvOrder::query()->where('order_type', 'owner')->where('status', 'completed')->orderByDesc('id')->first();
if ($ownerOrder) {
    echo "\n=== Sample owner order #{$ownerOrder->id} customer {$ownerOrder->customer_id} ===\n";
    $flags = TrustVerificationIssuedBadgeService::cardTrustBadgeFlags((int) $ownerOrder->customer_id);
    print_r($flags);
}
