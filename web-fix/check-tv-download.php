<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$c = App\Models\Customer::find(19);
$t = $c->createToken('dl-test')->plainTextToken;
$ch = curl_init('https://admin-homes.sukoon.group/api/trust-verification/orders/4/report/download');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$t],
]);
curl_exec($ch);
echo 'HTTP '.curl_getinfo($ch, CURLINFO_HTTP_CODE)."\n";
