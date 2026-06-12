<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$c = App\Models\Customer::find(15);
$t = $c->createToken('qa-15')->plainTextToken;
$ch = curl_init('https://admin-homes.sukoon.group/api/trust-verification/orders');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$t, 'Accept: application/json'],
]);
echo curl_exec($ch);
echo "\nHTTP ".curl_getinfo($ch, CURLINFO_HTTP_CODE)."\n";
