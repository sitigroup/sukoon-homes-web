<?php

$path = '/www/wwwroot/admin-homes/resources/views/customer/edit.blade.php';
$lines = file($path);
$out = [];
$skip = false;
$skipUntil = 0;

for ($i = 0; $i < count($lines); $i++) {
    $line = $lines[$i];
    if (str_contains($line, "Area Wise — direct create") && str_contains($lines[$i - 1] ?? '', 'col-md-4 mb-3')) {
        // Only skip if we're past the first Save button (agent profile section)
        $before = implode('', array_slice($lines, 0, $i));
        if (substr_count($before, "__('Save')") >= 1 && ! str_contains($before, 'Agent Profile')) {
            // first occurrence in update form — keep
            $out[] = $line;
            continue;
        }
        if (str_contains($before, 'Agent Profile')) {
            $skip = true;
            continue;
        }
    }
    if ($skip) {
        if (str_contains($line, 'Active Mode')) {
            $skip = false;
            $out[] = $line;
        }
        continue;
    }
    $out[] = $line;
}

file_put_contents($path, implode('', $out));
echo "OK\n";
