<?php

$path = '/www/wwwroot/admin-homes/resources/views/customer/edit.blade.php';
$content = file_get_contents($path);

$start = strpos($content, '<h4>{{ __(\'Agent Profile\') }}</h4>');
if ($start === false) {
    echo "FAIL\n";
    exit(1);
}

$section = substr($content, $start, 2500);
if (! str_contains($section, 'Area Wise — direct create')) {
    echo "OK: no duplicate in agent profile\n";
    exit(0);
}

$pattern = '/\s*<div class="col-md-4 mb-3">\s*<label class="form-label fw-bold">\{\{ __\(\'Area Wise — direct create\'\) \}\}\}<\/label>.*?<\/div>\s*/s';
$newSection = preg_replace($pattern, "\n", $section, 1);
$content = substr($content, 0, $start) . $newSection . substr($content, $start + strlen($section));
file_put_contents($path, $content);
echo "OK: removed duplicate from Agent Profile card\n";
