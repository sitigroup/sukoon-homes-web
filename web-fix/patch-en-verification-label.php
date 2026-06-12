<?php
$p = '/www/wwwroot/homes.sukoon.group/src/utils/en.json';
$d = json_decode(file_get_contents($p), true, 512, JSON_THROW_ON_ERROR);
$d['verificationServices'] = 'Verification';
file_put_contents($p, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
echo "ok\n";
