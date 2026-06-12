<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$ch = curl_init('https://admin-homes.sukoon.group/api/nearby-places/property/12/');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true]);
$json = json_decode(curl_exec($ch), true);
curl_close($ch);
foreach (['restaurant', 'hospital'] as $slug) {
    echo "=== $slug ===\n";
    foreach (($json['data']['places'][$slug] ?? []) as $p) {
        echo ($p['name'] ?? '') . ' | dist=' . ($p['distance_text'] ?? '') . ' | m=' . json_encode($p['distance_m']) . ' | same=' . json_encode($p['is_same_location'] ?? null) . ' | dir=' . ($p['directions_url'] ?? '') . "\n";
        if (!empty($p['_debug'])) {
            echo '  debug: ' . json_encode($p['_debug']) . "\n";
        }
    }
}
