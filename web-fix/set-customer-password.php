<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$id = (int) ($argv[1] ?? 15);
$password = $argv[2] ?? 'hems7827';
$user = App\Models\Customer::findOrFail($id);
$user->password = Illuminate\Support\Facades\Hash::make($password);
$user->is_email_verified = true;
$user->save();
echo "Customer #{$id} ({$user->email}) password updated. logintype={$user->logintype}\n";
