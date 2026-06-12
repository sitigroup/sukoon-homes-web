<?php

$token = $argv[1] ?? '';
if ($token === '') {
    fwrite(STDERR, "usage: php test-postman-live.php <bearer_token>\n");
    exit(1);
}

$tests = [
    ['GET', '/api/area-listing/permissions', ''],
    ['POST', '/api/location/suggest-area', '{"city_id":1,"name":"Postman Test Area"}'],
    ['POST', '/area-listing/repair-locations/dry-run', '{}'],
    ['POST', '/area-listing/drift-check', '{}'],
];

foreach ($tests as [$method, $path, $body]) {
    $ch = curl_init('https://admin-homes.sukoon.group' . $path);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
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

    echo $method . ' ' . $path . ' => ' . $code . PHP_EOL;
    echo substr((string) $response, 0, 220) . PHP_EOL . PHP_EOL;
}
