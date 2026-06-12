<?php

$path = '/www/wwwroot/admin-homes/app/Plugins/AreaListing/Services/AreaListingService.php';
$content = file_get_contents($path);

$old = <<<'PHP'
        foreach ($locationNames as $normalized => $rawName) {
            foreach ($areas as $area) {
                $areaNorm = self::normalized($area->name);

                foreach ($area->subAreas as $subArea) {
                    $subNorm = self::normalized($subArea->name);
                    if ($normalized === $subNorm) {
                        return self::formatResolvedLocation($area, $subArea, 'name', null, $rawName);
                    }
                }

                if ($normalized === $areaNorm) {
                    return self::formatResolvedLocation($area, null, 'name', null, $rawName);
                }
            }
        }

        return null;
PHP;

$new = <<<'PHP'
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

if (strpos($content, $old) === false) {
    echo "Pattern not found — already patched or file changed\n";
    exit(1);
}

file_put_contents($path, str_replace($old, $new, $content));
echo "Patched resolveFromAddressNames sub-area matching\n";
