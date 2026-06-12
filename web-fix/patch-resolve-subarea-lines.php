<?php

$path = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';
$lines = file($path);
if ($lines === false) {
    echo "Cannot read file\n";
    exit(1);
}

$start = null;
$end = null;
for ($i = 0; $i < count($lines); $i++) {
    if ($start === null && str_contains($lines[$i], 'foreach ($locationNames as $normalized => $rawName)')) {
        $fn = 0;
        for ($j = $i; $j >= 0; $j--) {
            if (str_contains($lines[$j], 'function resolveFromAddressNames')) {
                $fn = $j;
                break;
            }
        }
        if ($fn > 0) {
            $start = $i;
        }
    }
    if ($start !== null && $end === null && trim($lines[$i]) === 'return null;' && $i > $start) {
        $end = $i;
        break;
    }
}

if ($start === null || $end === null) {
    echo "Could not locate block start={$start} end={$end}\n";
    exit(1);
}

$replacement = [
    "        foreach (\$locationNames as \$normalized => \$rawName) {\n",
    "            foreach (\$areas as \$area) {\n",
    "                foreach (\$area->subAreas as \$subArea) {\n",
    "                    if (\$normalized === self::normalized(\$subArea->name)) {\n",
    "                        return self::formatResolvedLocation(\$area, \$subArea, 'name', null, \$rawName);\n",
    "                    }\n",
    "                }\n",
    "            }\n",
    "        }\n",
    "\n",
    "        foreach (\$locationNames as \$normalized => \$rawName) {\n",
    "            foreach (\$areas as \$area) {\n",
    "                if (\$normalized !== self::normalized(\$area->name)) {\n",
    "                    continue;\n",
    "                }\n",
    "\n",
    "                foreach (\$locationNames as \$subNormalized => \$subRawName) {\n",
    "                    foreach (\$area->subAreas as \$subArea) {\n",
    "                        if (\$subNormalized === self::normalized(\$subArea->name)) {\n",
    "                            return self::formatResolvedLocation(\$area, \$subArea, 'name', null, \$subRawName);\n",
    "                        }\n",
    "                    }\n",
    "                }\n",
    "\n",
    "                return self::formatResolvedLocation(\$area, null, 'name', null, \$rawName);\n",
    "            }\n",
    "        }\n",
    "\n",
    "        return null;\n",
];

array_splice($lines, $start, $end - $start + 1, $replacement);
file_put_contents($path, implode('', $lines));
echo "Replaced lines " . ($start + 1) . '-' . ($end + 1) . "\n";
