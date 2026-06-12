<?php
$file = '/www/wwwroot/homes.sukoon.group/src/utils/en.json';
$data = json_decode(file_get_contents($file), true);
$data['getDirections'] = 'Get Directions';
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
echo "ok\n";
