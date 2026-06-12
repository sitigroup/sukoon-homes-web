<?php

$path = '/www/wwwroot/admin-homes/resources/views/customer/edit.blade.php';
$content = file_get_contents($path);

$pattern = '/\s*@if\(\$customer->is_agent\)\s*<div class="col-12 mb-3">.*?Saved with the button below\.\'\) \}\}<\/small>\s*<\/div>\s*@endif\s*/s';
$new = $content;
$count = 0;
$new = preg_replace($pattern, "\n", $content, 1, $count);

if ($count < 1) {
    // Fallback: line-based between @if is_agent before Save and Save button
    $lines = file($path);
    $out = [];
    $skip = 0;
    foreach ($lines as $line) {
        if ($skip > 0) {
            $skip--;
            if (trim($line) === '@endif') {
                continue;
            }
            continue;
        }
        if (str_contains($line, '@if($customer->is_agent)') && str_contains($line, 'col-12 mb-3')) {
            $skip = 999;
            $buf = $line;
            continue;
        }
        if ($skip === 999) {
            if (str_contains($line, '@endif')) {
                $skip = 0;
            }
            continue;
        }
        $out[] = $line;
    }
    file_put_contents($path, implode('', $out));
    echo "OK line-based remove\n";
    exit(0);
}

file_put_contents($path, $new);
echo "OK regex remove\n";
