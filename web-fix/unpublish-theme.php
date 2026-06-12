<?php

$adminRoot = '/www/wwwroot/admin-homes';

require $adminRoot . '/vendor/autoload.php';
$app = require $adminRoot . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

App\Plugins\Theme\Services\ThemeService::unpublish();

echo "Theme unpublished.\n";
