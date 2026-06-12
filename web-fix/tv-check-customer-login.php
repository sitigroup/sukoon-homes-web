<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$id = (int) ($argv[1] ?? 15);
$c = App\Models\Customer::find($id);
if (! $c) {
    echo "Customer {$id} not found\n";
    exit(1);
}
echo "Customer #{$c->id}\n";
echo "  email={$c->email}\n";
echo "  mobile={$c->mobile}\n";
echo "  country_code={$c->country_code}\n";
echo "  logintype={$c->logintype}\n";

$email = $argv[2] ?? 'hemssarda@gmail.com';
echo "\nRows with email {$email}:\n";
foreach (App\Models\Customer::where('email', $email)->get(['id', 'email', 'mobile', 'country_code', 'logintype']) as $r) {
    echo "  #{$r->id} mobile={$r->mobile} cc={$r->country_code} logintype={$r->logintype}\n";
}
