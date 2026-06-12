<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$mods = config('rolepermission.modules', []);
$found = false;
foreach ($mods as $m) {
    if (($m['module'] ?? null) === 'whatsapp') { $found = true; break; }
}
echo $found ? "WHATSAPP_PERMISSION_MODULE_PRESENT\n" : "WHATSAPP_PERMISSION_MODULE_MISSING\n";