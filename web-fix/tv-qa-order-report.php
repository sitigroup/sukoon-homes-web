<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Services\TrustVerificationService;
use Illuminate\Support\Facades\Storage;

$num = $argv[1] ?? 'TV-EYMTZLKB';
$o = TvOrder::where('order_number', $num)->with('report')->first();
if (! $o) {
    echo "ORDER_NOT_FOUND\n";
    exit(1);
}

echo "order_id={$o->id}\n";
echo "status={$o->status}\n";
echo "payment={$o->payment_status}\n";
$r = $o->report;
echo 'report_id='.($r?->id ?? 'null')."\n";
echo 'file_path='.($r?->file_path ?? 'null')."\n";
echo 'deleted_at='.($r?->deleted_at ?? 'null')."\n";
echo 'reportFileExists='.(TrustVerificationService::reportFileExists($r) ? 'yes' : 'no')."\n";

$formatted = TrustVerificationService::formatOrder($o, true);
echo 'download_available='.(($formatted['report']['download_available'] ?? false) ? 'yes' : 'no')."\n";
echo 'uploaded='.(($formatted['report']['uploaded'] ?? false) ? 'yes' : 'no')."\n";

if ($r?->file_path) {
    foreach (['local', 'public'] as $disk) {
        try {
            $ok = Storage::disk($disk)->exists($r->file_path);
            echo "{$disk}_exists=".($ok ? 'yes' : 'no')."\n";
            if ($ok) {
                $path = Storage::disk($disk)->path($r->file_path);
                echo "{$disk}_path={$path}\n";
                if (is_file($path)) {
                    echo "{$disk}_size=".filesize($path)."\n";
                    echo "{$disk}_head=".substr(file_get_contents($path, false, null, 0, 5), 0, 5)."\n";
                }
            }
        } catch (Throwable $e) {
            echo "{$disk}_err=".$e->getMessage()."\n";
        }
    }
}
