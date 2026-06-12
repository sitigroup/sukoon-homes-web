<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Plugins\TrustVerification\Http\Controllers\Api\TrustVerificationApiController;
use App\Plugins\TrustVerification\Models\TvOrder;
use Illuminate\Http\Request;

$customerId = (int) ($argv[1] ?? 15);
$orderNumber = $argv[2] ?? 'TV-EYMTZLKB';

$customer = Customer::find($customerId);
if (! $customer) {
    echo "CUSTOMER_NOT_FOUND\n";
    exit(1);
}

$request = Request::create('/api/trust-verification/orders', 'GET');
$request->setUserResolver(fn () => $customer);

$response = app(TrustVerificationApiController::class)->orders($request);
$json = json_decode($response->getContent(), true);
$orders = $json['data'] ?? [];

$match = null;
foreach ($orders as $row) {
    if (($row['order_number'] ?? '') === $orderNumber) {
        $match = $row;
        break;
    }
}

echo json_encode([
    'http_status' => $response->getStatusCode(),
    'orders_count' => count($orders),
    'match' => $match ? [
        'id' => $match['id'],
        'order_number' => $match['order_number'],
        'status' => $match['status'],
        'payment_status' => $match['payment_status'],
        'report' => $match['report'] ?? null,
    ] : null,
], JSON_PRETTY_PRINT)."\n";
