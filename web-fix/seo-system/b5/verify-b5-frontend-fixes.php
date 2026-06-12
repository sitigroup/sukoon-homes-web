<?php
/** Quick live HTML checks for B5 frontend bug fixes. */
$base = 'https://homes.sukoon.group/rent/barmer/?lang=en';
$html = file_get_contents($base);

$out = [
    'url' => $base,
    'double_lang_in_hrefs' => preg_match('/\?lang=[^"\']+\?lang=/', $html) === 1,
    'sample_hrefs' => [],
    'listing_cities' => [],
];

preg_match_all('/href="([^"]+)"/', $html, $m);
foreach (array_slice($m[1] ?? [], 0, 15) as $href) {
    if (str_contains($href, '/rent/') || str_contains($href, '/property-details/')) {
        $out['sample_hrefs'][] = $href;
    }
}

preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $nd);
if ($nd) {
    $d = json_decode($nd[1], true);
    $listings = $d['props']['pageProps']['payload']['listings'] ?? [];
    foreach ($listings as $l) {
        $out['listing_cities'][] = ['title' => $l['title'] ?? '', 'city' => $l['city'] ?? ''];
    }
    $popular = $d['props']['pageProps']['popularPaths'] ?? [];
    $out['popular_paths'] = array_column($popular, 'path');
    $out['current_path'] = $d['props']['pageProps']['payload']['page']['path'] ?? null;
    $out['popular_includes_current'] = in_array($out['current_path'], $out['popular_paths'], true);
}

echo json_encode($out, JSON_PRETTY_PRINT) . PHP_EOL;
