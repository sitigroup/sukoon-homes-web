<?php
$base = '/www/wwwroot/admin-homes';
require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Services\TrustVerificationIssuedBadgeService;

foreach (TvOrder::where('order_type', 'owner')->orderByDesc('id')->limit(10)->get() as $o) {
    echo "#{$o->id} {$o->order_number} cust={$o->customer_id} status={$o->status} pay={$o->payment_status}\n";
}

echo "\nCard flags 15: " . json_encode(TrustVerificationIssuedBadgeService::cardTrustBadgeFlags(15)) . "\n";
