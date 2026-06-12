<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEnginePage;
use App\Plugins\SeoEngine\Services\SeoEngineContentService;
use App\Plugins\SeoEngine\Services\SeoEnginePageDataService;

$page = SeoEnginePage::query()->where('path', '/rent/barmer/')->first();
$payload = app(SeoEnginePageDataService::class)->getByPath('/rent/barmer/');
echo 'payload_ok=' . (is_array($payload) ? 'yes' : 'no') . "\n";

$svc = app(SeoEngineContentService::class);
$ref = new ReflectionClass($svc);
$m = $ref->getMethod('buildPrompt');
$m->setAccessible(true);
$prompt = $m->invoke($svc, $page);
echo 'prompt_len=' . strlen($prompt) . "\n";

$g = app(App\Services\GeminiService::class);
$r = $g->generateContent($prompt);
echo 'gemini_success=' . (($r['success'] ?? false) ? 'yes' : 'no') . "\n";
if (! ($r['success'] ?? false)) {
    echo 'error=' . ($r['error'] ?? '') . "\n";
} else {
    $text = $r['data']['candidates'][0]['content']['parts'][0]['text'] ?? '';
    echo 'text_len=' . strlen($text) . "\n";
    echo substr($text, 0, 500) . "\n";
}
