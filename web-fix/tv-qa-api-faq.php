<?php
$j = json_decode(file_get_contents('https://admin-homes.sukoon.group/api/trust-verification/content/public'), true);
foreach ($j['data']['faq'] ?? [] as $i) {
    if (str_contains($i['question'] ?? '', 'TASK12B')) {
        echo $i['question'] . "\n";
    }
}
