<?php
/**
 * Run generator twice; confirm zero junk paths after each run (no manual purge).
 */
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEnginePage;
use Illuminate\Support\Facades\Artisan;

$junk = ['baldev-nagar-barmer', 'raj-colneyer', 'saadsd'];

function junkPaths(array $patterns): array
{
    return SeoEnginePage::query()
        ->where(function ($q) use ($patterns) {
            foreach ($patterns as $p) {
                $q->orWhere('path', 'like', '%' . $p . '%');
            }
        })
        ->pluck('path')
        ->all();
}

$out = ['runs' => []];

foreach ([1, 2] as $run) {
    Artisan::call('seo-engine:generate-pages');
    $junkFound = junkPaths($junk);
    $out['runs'][] = [
        'run' => $run,
        'total' => SeoEnginePage::query()->count(),
        'indexable' => SeoEnginePage::query()->where('is_indexable', true)->pluck('path'),
        'junk_count' => count($junkFound),
        'junk_paths' => $junkFound,
    ];
}

$out['pass'] = $out['runs'][1]['junk_count'] === 0 && $out['runs'][0]['junk_count'] === 0;

echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
