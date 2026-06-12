<?php
/**
 * QA only: issue one verified SVO for a property lister (customer 15) using a dedicated owner order.
 * Run once: sudo -u www php /tmp/tv-qa-owner-badge.php
 */
$base = '/www/wwwroot/admin-homes';
require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Models\TvVerificationBadge;
use App\Plugins\TrustVerification\Services\TrustVerificationIssuedBadgeService;
use Illuminate\Support\Facades\DB;

$customerId = 15;
$listerProps = DB::table('propertys')->where('added_by', $customerId)->count();
echo "Properties for customer {$customerId}: {$listerProps}\n";

$ownerOrder = TvOrder::query()
    ->where('customer_id', $customerId)
    ->where('order_type', 'owner')
    ->where('status', 'completed')
    ->whereIn('payment_status', ['paid', 'waived'])
    ->whereNotIn('id', TvVerificationBadge::query()->select('order_id'))
    ->first();

if (! $ownerOrder) {
    echo "No completed owner order without badge for customer {$customerId}.\n";
    echo "Create/complete an owner verification order in admin, then:\n";
    echo "  php artisan trust-verification:issue-missing-badges --admin-verify\n";
    echo "Or issue from order panel with 'Issue as verified'.\n";
    exit(0);
}

$badge = TrustVerificationIssuedBadgeService::issueForOrder($ownerOrder, null, false, true);
echo "Issued {$badge->badge_number} ({$badge->badge_type}) for order #{$ownerOrder->id}\n";
echo 'owner_trust_badge: ' . (TrustVerificationIssuedBadgeService::cardTrustBadgeFlags($customerId)['owner_trust_badge'] ? 'true' : 'false') . "\n";
