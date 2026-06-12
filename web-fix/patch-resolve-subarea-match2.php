<?php

$path = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';
$content = file_get_contents($path);

$pattern = '/foreach \(\$locationNames as \$normalized => \$rawName\) \{\s*'
    . 'foreach \(\$areas as \$area\) \{\s*'
    . '\$areaNorm = self::normalized\(\$area->name\);\s*'
    . 'foreach \(\$area->subAreas as \$subArea\) \{[^}]+\}\s*'
    . 'if \(\$normalized === \$areaNorm\) \{[^}]+\}\s*'
    . '\}\s*'
    . '\}\s*'
    . 'return null;/s';

$replacement = <<<'PHP'
foreach ($locationNames as $normalized => $rawName) {
            foreach ($areas as $area) {
                foreach ($area->subAreas as $subArea) {
                    if ($normalized === self::normalized($subArea->name)) {
                        return self::formatResolvedLocation($area, $subArea, 'name', null, $rawName);
                    }
                }
            }
        }

        foreach ($locationNames as $normalized => $rawName) {
            foreach ($areas as $area) {
                if ($normalized !== self::normalized($area->name)) {
                    continue;
                }

                foreach ($locationNames as $subNormalized => $subRawName) {
                    foreach ($area->subAreas as $subArea) {
                        if ($subNormalized === self::normalized($subArea->name)) {
                            return self::formatResolvedLocation($area, $subArea, 'name', null, $subRawName);
                        }
                    }
                }

                return self::formatResolvedLocation($area, null, 'name', null, $rawName);
            }
        }

        return null;
PHP;

$newContent = preg_replace($pattern, $replacement, $content, 1, $count);
if ($count < 1) {
    echo "Regex replace failed\n";
    exit(1);
}

file_put_contents($path, $newContent);
echo "Patched OK\n";
