<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\TrustVerification\Models\TvOrder;
use App\Plugins\TrustVerification\Services\TrustVerificationService;
use Illuminate\Support\Facades\Storage;

$o = TvOrder::where('order_number', 'TV-EYMTZLKB')->with('report')->first();
$r = $o->report;
$path = $r->file_path;

echo 'user='.posix_getpwuid(posix_geteuid())['name'].PHP_EOL;
echo 'reportFileExists='.(TrustVerificationService::reportFileExists($r) ? 'yes' : 'no').PHP_EOL;
echo 'reportFileIsReadable='.(TrustVerificationService::reportFileIsReadable($r) ? 'yes' : 'no').PHP_EOL;

foreach (['local', 'public'] as $disk) {
    $exists = Storage::disk($disk)->exists($path);
    echo "{$disk}_exists=".($exists ? 'yes' : 'no').PHP_EOL;
    if ($exists) {
        try {
            $size = Storage::disk($disk)->size($path);
            echo "{$disk}_size={$size}".PHP_EOL;
            $stream = Storage::disk($disk)->readStream($path);
            $head = $stream ? fread($stream, 8) : '';
            if (is_resource($stream)) {
                fclose($stream);
            }
            echo "{$disk}_head=".bin2hex((string) $head).PHP_EOL;
        } catch (Throwable $e) {
            echo "{$disk}_err=".$e->getMessage().PHP_EOL;
        }
    }
}

$f = TrustVerificationService::formatOrder($o, true);
echo 'download_available='.(($f['report']['download_available'] ?? false) ? 'yes' : 'no').PHP_EOL;
echo 'awaiting_upload='.(($f['report']['awaiting_upload'] ?? false) ? 'yes' : 'no').PHP_EOL;
