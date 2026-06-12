<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Services\TrustVerificationIssuedBadgeService;
use Illuminate\Support\Facades\DB;

echo "=== Order eligibility blockers ===\n";
foreach (TvOrder::where('status', 'completed')->orderByDesc('id')->limit(10)->get() as $o) {
    $o = $o->fresh(['checkItems', 'policeVerification', 'documents', 'package']);
    echo "#{$o->id} type={$o->order_type} cust={$o->customer_id}\n";
    foreach (TrustVerificationIssuedBadgeService::eligibilityBlockers($o) as $b) {
        echo "  - $b\n";
    }
}

echo "\n=== Owner-type orders (any status) ===\n";
$ownerOrders = TvOrder::where('order_type', 'owner')->orderByDesc('id')->limit(10)->get();
echo 'count=' . $ownerOrders->count() . "\n";
foreach ($ownerOrders as $o) {
    echo "#{$o->id} status={$o->status} pay={$o->payment_status} cust={$o->customer_id}\n";
}

echo "\n=== Sample properties added_by ===\n";
try {
    $props = DB::table('propertys')->orderByDesc('id')->limit(8)->get(['id', 'added_by', 'title']);
    foreach ($props as $p) {
        echo "prop #{$p->id} added_by={$p->added_by}\n";
    }
} catch (Throwable $e) {
    echo $e->getMessage() . "\n";
}

echo "\n=== Try auto-issue on completed orders ===\n";
foreach (TvOrder::where('status', 'completed')->orderByDesc('id')->limit(5)->get() as $o) {
    $issued = TrustVerificationIssuedBadgeService::tryAutoIssue($o->fresh());
    echo "#{$o->id} issued=" . ($issued ? $issued->badge_number . ' ' . $issued->status : 'null') . "\n";
}

echo "\n=== Badges after try ===\n";
echo DB::table('tv_verification_badges')->count() . " rows\n";
