<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require_once '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Plugins\AreaListing\Services\AreaListingService;

$agents = Customer::query()->where('is_agent', 1)->orderBy('id')->get();

foreach ($agents as $a) {
    $can = AreaListingService::userCanAutoCreateAreas($a);
    echo sprintf(
        "id=%d email=%s is_agent=%s can_manage=%s autoCreate=%s class=%s\n",
        $a->id,
        $a->email,
        json_encode($a->is_agent),
        json_encode($a->can_manage_area_listing),
        $can ? 'yes' : 'no',
        get_class($a)
    );
}
