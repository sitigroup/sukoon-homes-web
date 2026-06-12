<?php
/**
 * Post-deploy trust badge verification (run on admin-homes server).
 * php /tmp/tv-verify-post-fix.php
 */
$base = is_file('/www/wwwroot/admin-homes/bootstrap/app.php')
    ? '/www/wwwroot/admin-homes'
    : dirname(__DIR__);
require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvVerificationBadge;
use App\Plugins\TrustVerification\Services\TrustVerificationIssuedBadgeService;
use App\Plugins\TrustVerification\Services\TrustVerificationTrustBadgeService;
use Illuminate\Support\Facades\DB;

echo "=== tv_verification_badges (last 10) ===\n";
$rows = DB::table('tv_verification_badges')->orderByDesc('id')->limit(10)->get();
echo 'count=' . DB::table('tv_verification_badges')->count() . "\n";
foreach ($rows as $r) {
    echo "#{$r->id} cust={$r->customer_id} order={$r->order_id} {$r->badge_type} {$r->badge_number} {$r->status}\n";
}

echo "\n=== Completed orders (last 8) ===\n";
foreach (TvOrder::where('status', 'completed')->orderByDesc('id')->limit(8)->get() as $o) {
    echo "#{$o->id} {$o->order_number} cust={$o->customer_id} type={$o->order_type} pay={$o->payment_status}\n";
}

echo "\n=== Public trust API samples ===\n";
foreach ([15, 19] as $cid) {
    $payload = TrustVerificationTrustBadgeService::publicTrustPayload($cid);
    echo "customer {$cid}: owner=" . ($payload['owner_trust_badge'] ? 'true' : 'false')
        . ' tenant=' . ($payload['tenant_trust_badge'] ? 'true' : 'false')
        . ' svo=' . ($payload['sukoon_verified_owner'] ? 'yes' : 'null') . "\n";
}

echo "\n=== Card flags ===\n";
foreach ([15, 19] as $cid) {
    $f = TrustVerificationIssuedBadgeService::cardTrustBadgeFlags($cid);
    echo "customer {$cid}: " . json_encode($f) . "\n";
}

echo "\n=== Owner completed orders ===\n";
$ownerDone = TvOrder::where('order_type', 'owner')->where('status', 'completed')->count();
echo "count={$ownerDone}\n";
