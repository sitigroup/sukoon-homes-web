<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
Illuminate\Support\Facades\DB::table('wa_settings')->updateOrInsert(
    ['id' => 1],
    [
        'verify_token' => 'sukoon_wa_verify_2026',
        'environment_mode' => 'test_number',
        'updated_at' => now(),
        'created_at' => now(),
    ]
);
echo "SEEDED\n";