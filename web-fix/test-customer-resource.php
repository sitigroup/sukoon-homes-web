<?php
declare(strict_types=1);

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Resources\CustomerResource;
use App\Models\Customer;

$id = (int) ($argv[1] ?? 15);
$customer = Customer::find($id);
if (! $customer) {
    echo "Customer {$id} not found\n";
    exit(1);
}

try {
    $data = (new CustomerResource($customer, [
        'is_agent',
        'is_user_verified',
        'is_appointment_available',
        'become_agent_status',
        'agent_verification_status',
        'user_verification_status',
    ]))->resolve();
    echo 'OK keys=' . implode(',', array_keys($data)) . PHP_EOL;
} catch (Throwable $e) {
    echo 'FAIL: ' . $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    exit(1);
}

if (class_exists(\App\Plugins\AreaListing\Services\AreaListingService::class)) {
    try {
        $can = \App\Plugins\AreaListing\Services\AreaListingService::userCanAutoCreateAreas($customer);
        echo 'can_manage_area_listing=' . ($can ? '1' : '0') . PHP_EOL;
    } catch (Throwable $e) {
        echo 'AreaListing FAIL: ' . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}
