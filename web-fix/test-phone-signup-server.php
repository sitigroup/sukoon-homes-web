<?php
declare(strict_types=1);

$payload = [
    'type' => '1',
    'mobile' => $argv[1] ?? '9990687827',
    'country_code' => $argv[2] ?? '91',
    'password' => $argv[3] ?? 'hems7827',
    'fcm_id' => 'test-fcm-token',
];

$ch = curl_init('https://admin-homes.sukoon.group/api/user_signup');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload),
]);
$body = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP {$code}\n{$body}\n";
