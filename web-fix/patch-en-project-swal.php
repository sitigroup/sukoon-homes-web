<?php
$file = '/www/wwwroot/homes.sukoon.group/src/utils/en.json';
$data = json_decode(file_get_contents($file), true);
if (!is_array($data)) {
    fwrite(STDERR, "invalid json\n");
    exit(1);
}
$data['agentSwitchSwalTextProject'] =
    'This project was posted from your agent account. Switch to agent mode to manage it, or choose No to view the public project page.';
file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
echo "ok\n";
