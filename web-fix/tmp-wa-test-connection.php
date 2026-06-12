<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$svc = app(App\Plugins\Whatsapp\Services\MetaGraphClient::class);
$settings = $svc->settings();
if (!$settings) {
    echo "NO_SETTINGS\n";
    exit(0);
}
echo "PHONE_NUMBER_ID=" . ($settings->phone_number_id ?? '') . "\n";
echo "WABA_ID=" . ($settings->waba_id ?? '') . "\n";
echo "TOKEN_SET=" . (!empty($settings->access_token) ? 'YES' : 'NO') . "\n";
$result = $svc->testConnection();
echo "RESULT=\n";
echo json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . "\n";