<?php
$base = '/www/wwwroot/admin-homes';
require $base . '/vendor/autoload.php';
$app = require_once $base . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Owner badges ===\n";
foreach (DB::table('tv_verification_badges')->where('badge_type', 'owner')->orderByDesc('id')->get() as $r) {
    echo "#{$r->id} cust={$r->customer_id} order={$r->order_id} {$r->badge_number} {$r->status}\n";
}

echo "\n=== Owner order #19 ===\n";
$o = DB::table('tv_orders')->where('id', 19)->first();
if ($o) {
    echo "cust={$o->customer_id} type={$o->order_type} status={$o->status} pay={$o->payment_status}\n";
}
