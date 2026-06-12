<?php

$token = $argv[1] ?? '';
$url = $argv[2] ?? '/area-listing/repair-locations/dry-run';

$ch = curl_init('https://admin-homes.sukoon.group' . $url . '?token=' . urlencode($token));
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo $url . ' (query token only) => ' . $code . PHP_EOL;
echo substr((string) $response, 0, 120) . PHP_EOL;
