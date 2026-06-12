<?php
$json = file_get_contents('https://admin-homes.sukoon.group/api/get_property?id=32');
$data = json_decode($json, true);
$params = $data['data'][0]['parameters'] ?? [];
echo json_encode($params, JSON_PRETTY_PRINT) . PHP_EOL;
