<?php

$root = '/www/wwwroot/admin-homes';
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::find(1);
if (! $user) {
    echo "no user\n";
    exit(1);
}

$token = $user->createToken('cli-repair-drift-test')->plainTextToken;
echo "token_len=" . strlen($token) . PHP_EOL;

$urls = [
    'web_repair' => '/area-listing/repair-locations/dry-run',
    'web_drift' => '/area-listing/drift-check',
    'api_repair' => '/api/area-listing/repair-locations/dry-run',
    'api_drift' => '/api/area-listing/drift-check',
];

foreach ($urls as $label => $uri) {
    $request = Illuminate\Http\Request::create($uri, 'POST', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        'CONTENT_TYPE' => 'application/json',
    ], '{}');

    $response = app()->handle($request);
    $body = json_decode($response->getContent(), true);
    echo $label . ' status=' . $response->getStatusCode();
    if (is_array($body)) {
        if (isset($body['missing'])) {
            echo ' missing=' . $body['missing'];
        }
        if (isset($body['total_drift'])) {
            echo ' total_drift=' . $body['total_drift'];
        }
        if (isset($body['message'])) {
            echo ' msg=' . substr((string) $body['message'], 0, 60);
        }
    }
    echo PHP_EOL;
}

$user->tokens()->where('name', 'cli-repair-drift-test')->delete();
echo "token revoked\n";
