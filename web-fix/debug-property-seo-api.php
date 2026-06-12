<?php
$json = file_get_contents('https://admin-homes.sukoon.group/api/get_property?slug_id=home-for-rent&with_seo=1');
$data = json_decode($json, true);
$p = $data['data'][0] ?? [];
echo json_encode([
    'meta_title' => $p['meta_title'] ?? null,
    'meta_description' => $p['meta_description'] ?? null,
    'meta_keywords' => $p['meta_keywords'] ?? null,
    'meta_image' => $p['meta_image'] ?? null,
    'schema_markup' => $p['schema_markup'] ?? null,
], JSON_PRETTY_PRINT) . PHP_EOL;
