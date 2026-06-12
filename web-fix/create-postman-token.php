<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$userId = (int) ($argv[1] ?? 1);
$tokenName = $argv[2] ?? 'postman-area-api-test';

$user = App\Models\User::find($userId);
if (! $user) {
    fwrite(STDERR, "User {$userId} not found\n");
    exit(1);
}

$user->tokens()->where('name', $tokenName)->delete();
$token = $user->createToken($tokenName);

echo json_encode([
    'user_id' => (int) $user->id,
    'email' => (string) ($user->email ?? ''),
    'token_name' => $tokenName,
    'bearer_token' => $token->plainTextToken,
    'usage' => 'Authorization: Bearer <bearer_token>',
    'base_url' => 'https://admin-homes.sukoon.group',
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
