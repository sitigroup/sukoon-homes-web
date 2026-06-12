<?php

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\AgentVerification;

$email = $argv[1] ?? 'hemssarda@gmail.com';
$c = Customer::where('email', $email)->first();
if (!$c) {
    echo "NOT_FOUND\n";
    exit(1);
}

$become = AgentVerification::where('customer_id', $c->id)->where('form_type', 'become_agent')->first();
$verify = AgentVerification::where('customer_id', $c->id)->where('form_type', 'verify_agent')->first();

echo json_encode([
    'customer' => [
        'id' => $c->id,
        'is_agent' => $c->is_agent,
        'can_manage_area_listing' => $c->can_manage_area_listing,
    ],
    'become_agent' => $become ? ['status' => $become->status] : null,
    'verify_agent' => $verify ? ['status' => $verify->status] : null,
], JSON_PRETTY_PRINT) . "\n";
