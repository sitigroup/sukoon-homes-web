<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$o = App\Plugins\TrustVerification\Models\TvOrder::with('report')->find(4);
echo App\Plugins\TrustVerification\Services\TrustVerificationService::reportFileExists($o->report) ? "report_ok\n" : "report_missing\n";
