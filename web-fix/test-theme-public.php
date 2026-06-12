<?php
declare(strict_types=1);

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Plugins\Theme\Models\ThemeSetting;
use App\Plugins\Theme\Services\ThemeService;
use Illuminate\Support\Facades\Schema;

echo 'table=' . (Schema::hasTable('theme_settings') ? 'yes' : 'no') . PHP_EOL;

try {
    $setting = ThemeSetting::query()->first();
    echo 'row=' . ($setting ? 'yes id='.$setting->id : 'no') . PHP_EOL;
    echo 'published_payload=' . (is_array($setting?->published_payload) ? 'array' : gettype($setting?->published_payload)) . PHP_EOL;
    echo 'isPublished=' . (ThemeService::isPublished() ? '1' : '0') . PHP_EOL;
    $resp = ThemeService::publicApiResponse();
    echo 'OK message=' . ($resp['message'] ?? '') . PHP_EOL;
} catch (Throwable $e) {
    echo 'FAIL: ' . $e->getMessage() . PHP_EOL;
    echo $e->getFile() . ':' . $e->getLine() . PHP_EOL;
}
