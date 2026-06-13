<?php
/**
 * One-off Phase C verify — run on admin server: php verify-phase-c.php
 */
$base = getenv('API_BASE') ?: 'https://admin-homes.sukoon.group/api';

$payload = [
    'name' => 'Phase C Verify',
    'phone' => '9999900001',
    'requirement' => '2BHK automated test',
    'source_path' => '/rent/barmer/',
    'form_type' => 'lead',
    'website' => '',
];

$ch = curl_init("{$base}/seo-engine/leads");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'X-Active-Role: user'],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
]);
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP {$code}\n";
echo $body . "\n";
