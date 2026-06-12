<?php
$j = json_decode(file_get_contents('https://admin-homes.sukoon.group/api/nearby-places/property/12/'), true);
$p = $j['data']['places']['restaurant'][0] ?? [];
$keys = ['name', 'distance_m', 'distance_text', 'is_same_location', 'directions_url', 'google_place_id', 'rating', 'is_open'];
$out = array_intersect_key($p, array_flip($keys));
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
echo 'has_debug=' . (isset($p['_debug']) ? 'yes' : 'no') . PHP_EOL;
