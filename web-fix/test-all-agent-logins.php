<?php
declare(strict_types=1);

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Plugins\AreaListing\Services\AreaListingService;

$agents = Customer::query()
    ->where('is_agent', 1)
    ->orderByDesc('id')
    ->limit(50)
    ->get();

foreach ($agents as $customer) {
    try {
        $loginData = (new CustomerResource($customer, [
            'is_agent',
            'is_user_verified',
            'is_appointment_available',
            'become_agent_status',
            'agent_verification_status',
            'user_verification_status',
        ]))->resolve();
        if (class_exists(AreaListingService::class)) {
            $loginData['can_manage_area_listing'] = AreaListingService::userCanAutoCreateAreas($customer);
        }
        echo "OK id={$customer->id} email={$customer->email}\n";
    } catch (Throwable $e) {
        echo "FAIL id={$customer->id} email={$customer->email}: {$e->getMessage()}\n";
    }
}
