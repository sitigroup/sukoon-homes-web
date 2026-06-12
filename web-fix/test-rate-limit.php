<?php

$token = $argv[1] ?? '';
if ($token === '') {
    exit(1);
}

$body = json_encode([
    'city_id' => 1,
    'city' => 'Barmer',
    'name' => 'Rate Limit Test ' . uniqid(),
]);

for ($i = 1; $i <= 7; $i++) {
    $ch = curl_init('https://admin-homes.sukoon.group/api/location/suggest-area');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "request {$i} => {$code}\n";
    if ($code === 429) {
        $data = json_decode((string) $response, true);
        echo '  retry-after: ' . ($data['retry_after'] ?? 'n/a') . "\n";
    }
}
