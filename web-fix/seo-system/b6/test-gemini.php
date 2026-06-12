<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$g = app(App\Services\GeminiService::class);
$r = $g->generateContent('Say hello in one word.');
echo json_encode($r, JSON_PRETTY_PRINT) . "\n";
echo 'api_key_set=' . (config('services.gemini.api_key') ? 'yes' : 'no') . "\n";
echo 'api_url=' . config('services.gemini.api_url') . "\n";
