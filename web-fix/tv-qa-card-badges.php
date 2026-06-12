<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\TrustVerification\Services\TrustVerificationTrustBadgeService;

foreach ([15, 19] as $id) {
    $p = TrustVerificationTrustBadgeService::formatForApi($id, true);
    if (! $p) {
        echo "customer {$id}: no public trust\n";
        continue;
    }
    echo "customer {$id}: owner=".($p['owner_trust_badge'] ? 'true' : 'false');
    echo ' tenant='.($p['tenant_trust_badge'] ? 'true' : 'false');
    $slugs = array_column($p['badges'] ?? [], 'slug');
    echo ' catalog_slugs='.implode(',', $slugs)."\n";
}
