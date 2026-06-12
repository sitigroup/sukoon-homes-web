<?php
$base = '/www/wwwroot/admin-homes';
chdir($base);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\TrustVerification\Models\TvRiskProfile;
use App\Plugins\TrustVerification\Models\TvRiskSignal;
use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Services\TrustVerificationFraudRiskService;

$customerId = (int) ($argv[1] ?? 19);

$before = TvRiskProfile::where('customer_id', $customerId)->first();
echo "BEFORE customer {$customerId}: score=".($before->risk_score ?? 'none')." level=".($before->risk_level ?? 'none').PHP_EOL;

TrustVerificationFraudRiskService::addManualFlag(
    $customerId,
    12,
    'TASK-17B QA manual fraud flag',
    null,
    1
);

$after = TvRiskProfile::where('customer_id', $customerId)->first();
echo "AFTER manual flag: score={$after->risk_score} level={$after->risk_level} signals=".TvRiskSignal::where('customer_id', $customerId)->count().PHP_EOL;

$manual = TvRiskSignal::where('customer_id', $customerId)->where('source', 'manual')->latest('id')->first();
echo "MANUAL_SIGNAL: type={$manual->signal_type} points={$manual->risk_points}".PHP_EOL;

echo PHP_EOL."FILTER COUNTS:".PHP_EOL;
foreach (['low', 'medium', 'high', 'critical'] as $lvl) {
    echo "  level_{$lvl}=".TvRiskProfile::where('risk_level', $lvl)->count().PHP_EOL;
}
echo '  duplicate_phone='.TvRiskProfile::whereIn('customer_id', function ($q) {
    $q->select('customer_id')->from('tv_risk_signals')->where('signal_type', TrustVerificationFraudRiskService::SIGNAL_DUPLICATE_MOBILE);
})->count().PHP_EOL;
echo '  rejected_police='.TvRiskProfile::whereIn('customer_id', function ($q) {
    $q->select('customer_id')->from('tv_risk_signals')->where('signal_type', TrustVerificationFraudRiskService::SIGNAL_POLICE_REJECTED);
})->count().PHP_EOL;
echo '  failed_reference='.TvRiskProfile::whereIn('customer_id', function ($q) {
    $q->select('customer_id')->from('tv_risk_signals')->where('signal_type', TrustVerificationFraudRiskService::SIGNAL_REFERENCE_FAILED);
})->count().PHP_EOL;

$order = TvOrder::where('customer_id', $customerId)->orderByDesc('id')->first();
if ($order) {
    echo PHP_EOL."ORDER_LOAD_OK id={$order->id} number={$order->order_number}".PHP_EOL;
    $order->load(['package', 'subject', 'checkItems', 'report']);
    echo 'REPORT_ROW='.($order->report ? 'yes risk_level='.$order->report->risk_level : 'no').PHP_EOL;
}
