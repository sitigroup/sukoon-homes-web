<?php
/**
 * B7 verify — published guide public render + sitemap (run on server after npm build).
 */
$base = 'https://homes.sukoon.group';
$api = 'https://admin-homes.sukoon.group/api';

require '/www/wwwroot/admin-homes/vendor/autoload.php';
$app = require '/www/wwwroot/admin-homes/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Plugins\SeoEngine\Models\SeoEngineQaPage;

$guide = SeoEngineQaPage::query()->where('status', 'published')->orderBy('id')->first();
if (! $guide) {
    echo json_encode(['error' => 'no published guide'], JSON_PRETTY_PRINT) . PHP_EOL;
    exit(1);
}

$path = $guide->publicPath();
$url = $base . $path . '?lang=en';

$out = [
    'guide' => ['id' => $guide->id, 'category' => $guide->category, 'slug' => $guide->slug, 'path' => $path],
    'checks' => [],
];

// Public HTML
$html = @file_get_contents($url);
$out['checks']['public_http'] = $html !== false ? 200 : 0;
$out['checks']['has_direct_answer_box'] = $html && (
    str_contains($html, 'Direct answer')
    || str_contains($html, '"direct_answer"')
);
$out['checks']['has_h1_question'] = $html && str_contains($html, htmlspecialchars($guide->question, ENT_QUOTES));
$out['checks']['has_faqpage_ld'] = $html && (
    str_contains($html, 'FAQPage')
    || str_contains($html, '"@type":"FAQPage"')
    || str_contains($html, '"@type": "FAQPage"')
);
$out['checks']['has_article_ld'] = $html && (
    str_contains($html, '"@type":"Article"')
    || str_contains($html, '"@type": "Article"')
    || (str_contains($html, 'Article') && str_contains($html, 'headline'))
);

// API
$apiJson = @file_get_contents($api . '/seo-engine/qa-page?category=' . urlencode($guide->category) . '&slug=' . urlencode($guide->slug));
$apiData = $apiJson ? json_decode($apiJson, true) : null;
$out['checks']['api_ok'] = ($apiData['error'] ?? true) === false;

// qa-guides.json
$qaFile = '/www/wwwroot/admin-homes/storage/app/seo-engine/qa-guides.json';
$qaUrls = is_readable($qaFile) ? json_decode(file_get_contents($qaFile), true) : [];
$out['checks']['qa_guides_json_count'] = is_array($qaUrls) ? count($qaUrls) : 0;
$out['checks']['qa_guides_json_has_path'] = is_array($qaUrls) && collect($qaUrls)->contains(fn ($u) => ($u['path'] ?? '') === $path);

// Sitemap index + child
$sitemapIndex = @file_get_contents($base . '/sitemap.xml');
$out['checks']['sitemap_has_qa_guides'] = $sitemapIndex && str_contains($sitemapIndex, 'qa-guides.xml');
$qaXml = @file_get_contents($base . '/sitemaps/qa-guides.xml');
$out['checks']['qa_guides_xml_has_url'] = $qaXml && str_contains($qaXml, $path);

$out['pass'] = ! in_array(false, $out['checks'], true) && ! in_array(0, $out['checks'], true);

echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
exit($out['pass'] ? 0 : 1);
