<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo Schema::hasTable('tv_tenant_reliability') ? "table_yes\n" : "table_no\n";
