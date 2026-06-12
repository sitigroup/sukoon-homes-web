<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "areas: ";
try {
    $r = App\Plugins\AreaListing\Services\AreaListingService::areas(['city_id' => 1]);
    echo (is_countable($r) ? count($r) : 'ok') . PHP_EOL;
} catch (Throwable $e) {
    echo $e->getMessage() . PHP_EOL;
}

echo "actor sanctum sim: ";
try {
    $user = App\Models\User::find(1);
    if ($user) {
        echo 'User id='.$user->id.' active_attr=';
        var_export(@$user->isActive);
        echo PHP_EOL;
    }
} catch (Throwable $e) {
    echo $e->getMessage() . PHP_EOL;
}
