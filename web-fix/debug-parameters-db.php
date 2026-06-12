<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo json_encode(DB::table('parameters')->select('id', 'name', 'image')->orderBy('id')->get(), JSON_PRETTY_PRINT) . PHP_EOL;
