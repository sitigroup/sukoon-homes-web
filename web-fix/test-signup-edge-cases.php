<?php
declare(strict_types=1);

function hit(array $payload): void
{
    $ch = curl_init('https://admin-homes.sukoon.group/api/user_signup');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
    ]);
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo json_encode($payload) . " => HTTP {$code} len=" . strlen($body) . "\n";
    if ($code >= 500) {
        echo $body . "\n";
    }
}

hit(['type' => '3', 'email' => 'hemssarda@gmail.com', 'password' => 'wrong']);
hit(['type' => '1', 'mobile' => '9990687827', 'country_code' => '+91', 'password' => 'hems7827']);
hit(['type' => '3', 'email' => 'hemssarda@gmail.com', 'password' => 'hems7827', 'fcm_id' => str_repeat('x', 5000)]);
hit(['type' => '3', 'email' => 'hemssarda@gmail.com', 'password' => 'hems7827', 'fcm_id' => ['bad' => 'array']]);
