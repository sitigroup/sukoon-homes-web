<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$s = Illuminate\Support\Facades\DB::table('wa_settings')->orderByDesc('id')->first();
if (!$s) {
    echo "NO_SETTINGS\n";
    exit(0);
}
echo "ID={$s->id}\n";
echo "VERIFY_TOKEN={$s->verify_token}\n";
echo "MODE={$s->environment_mode}\n";