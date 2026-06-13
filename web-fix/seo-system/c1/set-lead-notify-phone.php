<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$s = app(App\Plugins\SeoEngine\Services\SeoEngineSettingsService::class);
$s->set('lead_notify_phone', '+919990687827', 'leads');
echo $s->get('lead_notify_phone') . PHP_EOL;
