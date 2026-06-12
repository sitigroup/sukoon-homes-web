<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$o = App\Plugins\TrustVerification\Models\TvOrder::find(17);
echo 'order17 customer_id='.($o->customer_id ?? 'null').' status='.$o->status.PHP_EOL;
echo 'requires_police='.(App\Plugins\TrustVerification\Services\TrustVerificationPoliceVerificationService::orderRequiresPoliceVerification($o) ? 'yes' : 'no').PHP_EOL;
