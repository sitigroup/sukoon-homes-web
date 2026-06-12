<?php
// verify-b5-rent.php - run on server via curl fetch to localhost or save html
$urls = [
    'indexable' => 'https://homes.sukoon.group/rent/barmer/?lang=en',
    'noindex' => 'https://homes.sukoon.group/rent/barmer/baldev-nagar/?lang=en',
    'robots' => 'https://homes.sukoon.group/robots.txt',
];

$out = [];
foreach ($urls as $key => $url) {
    $html = file_get_contents($url);
    $out[$key] = ['url' => $url, 'len' => strlen($html)];
    if ($key !== 'robots') {
        preg_match('/<script id="__NEXT_DATA__" type="application\/json">(.*?)<\/script>/s', $html, $m);
        if ($m) {
            $d = json_decode($m[1], true);
            $pp = $d['props']['pageProps'] ?? [];
            $out[$key]['title'] = $pp['payload']['page']['title'] ?? null;
            $out[$key]['robots'] = $pp['robots'] ?? null;
            $out[$key]['listings'] = count($pp['payload']['listings'] ?? []);
            $out[$key]['has_structured'] = ! empty($pp['structuredData']);
            $out[$key]['h1'] = preg_match('/<h1[^>]*>([^<]+)/', $html, $h) ? $h[1] : null;
        }
        preg_match('/<meta name="robots" content="([^"]+)"/', $html, $rm);
        $out[$key]['meta_robots'] = $rm[1] ?? null;
    } else {
        $out[$key]['has_sitemap'] = str_contains($html, 'Sitemap:');
        $out[$key]['has_disallow_login'] = str_contains($html, 'Disallow: /login');
        $out[$key]['has_gptbot'] = str_contains($html, 'GPTBot');
        $out[$key]['snippet'] = implode("\n", array_slice(explode("\n", $html), 0, 30));
    }
}

echo json_encode($out, JSON_PRETTY_PRINT) . "\n";
