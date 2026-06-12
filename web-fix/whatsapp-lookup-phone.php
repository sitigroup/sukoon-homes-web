<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$digits = preg_replace('/\D+/', '', $argv[1] ?? '');
if (strlen($digits) === 10) {
    $meta = '91' . $digits;
} else {
    $meta = $digits;
}

$customers = DB::table('customers')
    ->where('mobile', 'like', '%' . substr($digits, -10))
    ->limit(5)
    ->get(['id', 'name', 'mobile']);

$contacts = DB::table('wa_contacts')
    ->where('phone', 'like', '%' . substr($digits, -10))
    ->orWhere('phone', $meta)
    ->get(['id', 'phone', 'customer_id', 'customer_type', 'opted_in']);

echo json_encode([
    'input' => $argv[1] ?? '',
    'meta_to' => $meta,
    'customers' => $customers,
    'wa_contacts' => $contacts,
], JSON_PRETTY_PRINT) . PHP_EOL;
