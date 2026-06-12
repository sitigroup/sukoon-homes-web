<?php

$base = '/www/wwwroot/admin-homes';
chdir($base);
require $base . '/vendor/autoload.php';
$app = require $base . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$num = $argv[1] ?? 'TV-H0IHINHA';
$o = App\Plugins\TrustVerification\Models\TvOrder::where('order_number', $num)->with('report')->first();
if (! $o) {
    echo "order not found\n";
    exit(1);
}
echo "order_id={$o->id} status={$o->status} payment={$o->payment_status}\n";
if (! $o->report) {
    echo "no report record\n";
    exit(0);
}
$r = $o->report;
echo "report_id={$r->id}\n";
echo "file_path={$r->file_path}\n";
echo "risk_level={$r->risk_level}\n";
$exists = App\Plugins\TrustVerification\Services\TrustVerificationService::reportFileExists($r);
echo "file_exists=" . ($exists ? 'yes' : 'no') . "\n";
if ($exists && $r->file_path) {
    $disk = App\Plugins\TrustVerification\Services\TrustVerificationService::reportDiskForPath($r->file_path);
    $full = $disk ? storage_path('app/' . $disk . '/' . $r->file_path) : null;
    if ($full && is_file($full)) {
        echo "full_path={$full}\n";
        echo "size=" . filesize($full) . "\n";
        $head = file_get_contents($full, false, null, 0, 8);
        echo "magic=" . bin2hex($head) . " (" . substr($head, 0, 5) . ")\n";
    } else {
        echo "resolved path missing\n";
        foreach (['public', 'local'] as $d) {
            $try = storage_path("app/{$d}/{$r->file_path}");
            if (is_file($try)) {
                echo "found_at={$try} size=" . filesize($try) . "\n";
            }
        }
    }
}
