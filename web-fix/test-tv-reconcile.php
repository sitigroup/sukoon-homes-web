<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$controller = file_get_contents('/www/wwwroot/admin-homes/app/Plugins/TrustVerification/Http/Controllers/Admin/TrustVerificationAutomationAdminController.php');
echo (str_contains($controller, 'Artisan::call') ? "OLD controller (Artisan)\n" : "NEW controller (service)\n");

$result = App\Plugins\TrustVerification\Services\TrustVerificationPaymentService::reconcilePendingPayments(false);
echo $result['message']."\n";
