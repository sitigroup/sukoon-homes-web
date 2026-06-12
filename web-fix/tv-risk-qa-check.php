<?php
$base = '/www/wwwroot/admin-homes';
chdir($base);
require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\TrustVerification\Models\TvRiskProfile;
use App\Plugins\TrustVerification\Models\TvRiskSignal;
use App\Plugins\TrustVerification\Services\TrustVerificationTrustBadgeService;

$profiles = TvRiskProfile::orderByDesc('risk_score')->get();
$signals = TvRiskSignal::count();

echo "profiles_count=".TvRiskProfile::count().PHP_EOL;
echo "signals_count=".$signals.PHP_EOL;
foreach ($profiles as $p) {
    echo "customer {$p->customer_id}: score={$p->risk_score} level={$p->risk_level} override={$p->manual_override}".PHP_EOL;
}

$public = TrustVerificationTrustBadgeService::formatForApi(15, true);
$json = json_encode($public);
echo 'public_trust_has_risk='.(str_contains($json, 'risk_score') || str_contains($json, 'risk_level') ? 'YES_FAIL' : 'NO_OK').PHP_EOL;
