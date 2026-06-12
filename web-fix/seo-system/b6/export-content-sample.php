<?php
require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEnginePage;

$paths = array_slice(array_map('trim', explode(',', $argv[1] ?? '/rent/barmer/')), 0, 10);

$out = [];
foreach ($paths as $path) {
    if ($path === '') continue;
    if (! str_ends_with($path, '/')) $path .= '/';
    $page = SeoEnginePage::query()->where('path', $path)->first();
    if (! $page) {
        $out[] = ['path' => $path, 'error' => 'not found'];
        continue;
    }
    $out[] = [
        'path' => $page->path,
        'is_indexable' => $page->is_indexable,
        'intro_html' => $page->intro_html,
        'faq_json' => $page->faq_json,
        'content_review_status' => $page->content_review_status,
    ];
}

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
