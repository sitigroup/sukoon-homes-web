<?php
$ch = curl_init('https://admin-homes.sukoon.group/api/user_signup');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'type' => '3',
        'email' => $argv[1] ?? 'hemssarda@gmail.com',
        'password' => $argv[2] ?? 'hems7827',
    ]),
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP {$code}\n{$body}\n";
