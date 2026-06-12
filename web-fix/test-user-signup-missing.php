<?php
declare(strict_types=1);

$payload = [
    'type' => '3',
    'email' => 'nonexistent-agent-' . time() . '@example.com',
    'password' => 'wrongpassword123',
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
