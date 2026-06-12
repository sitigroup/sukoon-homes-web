<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;

$email = $argv[1] ?? 'hemssarda@gmail.com';
$c = Customer::where('email', $email)->first();

if (!$c) {
    echo "NOT_FOUND: {$email}\n";
    exit(1);
}

if (!$c->is_agent) {
    echo "WARN: customer is not marked as agent — enabling is_agent\n";
    $c->is_agent = 1;
}

$c->can_manage_area_listing = 1;
$c->save();

echo "OK: enabled Area Wise for {$email} (id {$c->id})\n";
