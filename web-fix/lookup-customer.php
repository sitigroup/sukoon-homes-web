<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$mobile = $argv[1] ?? '9990687827';
$c = App\Models\Customer::where('mobile', $mobile)
    ->orWhere('mobile', 'like', '%'.$mobile)
    ->first();
if (! $c) {
    echo "not found\n";
    exit(1);
}
echo "id={$c->id} email={$c->email} mobile={$c->mobile} logintype={$c->logintype}\n";
