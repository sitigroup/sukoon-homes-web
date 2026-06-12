<?php
/**
 * Simulate email forgot-password: update password + login (customer #15).
 * Usage: php test-forgot-password-flow.php [email] [password]
 */
$email = $argv[1] ?? 'hemssarda@gmail.com';
$password = $argv[2] ?? 'hems7827';
$base = 'https://admin-homes.sukoon.group/api';

function api(string $method, string $url, array $json = null): array
{
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($json !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $code, 'body' => json_decode($body, true) ?: $body];
}

$up = api('POST', "$base/update-email-password", [
    'email' => $email,
    'password' => $password,
    're_password' => $password,
]);
echo "UPDATE PASSWORD HTTP {$up['code']}: ".json_encode($up['body'])."\n";

$login = api('POST', "$base/user_signup", [
    'type' => '3',
    'email' => $email,
    'password' => $password,
]);
echo "LOGIN HTTP {$login['code']}: ".(is_array($login['body']) ? ($login['body']['message'] ?? json_encode($login['body'])) : $login['body'])."\n";
if (! empty($login['body']['token'])) {
    echo "LOGIN OK — token received\n";
}
