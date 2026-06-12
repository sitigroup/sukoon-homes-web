<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$email = $argv[1] ?? 'hemssarda@gmail.com';
$rows = App\Models\Customer::where('email', $email)->get(['id', 'email', 'mobile', 'logintype', 'is_email_verified']);
foreach ($rows as $c) {
    echo "id={$c->id} logintype={$c->logintype} mobile={$c->mobile} verified={$c->is_email_verified}\n";
}
if ($rows->isEmpty()) {
    echo "No customer with email {$email}\n";
}
