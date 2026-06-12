<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$c = App\Models\Customer::find(15);
$pw = $argv[1] ?? 'hems7827';
echo 'logintype='.$c->logintype."\n";
echo 'password_check='.(Illuminate\Support\Facades\Hash::check($pw, $c->password) ? 'yes' : 'no')."\n";
$orders = App\Plugins\TrustVerification\Models\TvOrder::where('customer_id', 15)->count();
echo "tv_orders={$orders}\n";
