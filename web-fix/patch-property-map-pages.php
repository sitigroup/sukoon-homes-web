<?php

$files = [
    '/www/wwwroot/admin-homes/resources/views/property/create.blade.php',
    '/www/wwwroot/admin-homes/resources/views/project/edit.blade.php',
    '/www/wwwroot/admin-homes/resources/views/project/create.blade.php',
];

foreach ($files as $file) {
    if (!is_file($file)) {
        echo "SKIP missing {$file}\n";
        continue;
    }
    $content = file_get_contents($file);
    $content = preg_replace('/maps-helper\.js\)\s*\}\}\?v=[^"]+/', "maps-helper.js') }}?v=20260521pinfix", $content);
    $content = str_replace('var backendPlacesMapOptions = {', 'window.backendPlacesMapOptions = {', $content);
    if (!str_contains($content, '__backendPlacesMapPendingOptions')) {
        $content = preg_replace(
            '/(window\.backendPlacesMapOptions = \{[\s\S]*?\n\s*\});/',
            "$1;\n        window.__backendPlacesMapPendingOptions = window.backendPlacesMapOptions",
            $content,
            1
        );
    }
    $content = str_replace('initBackendPlacesMap(backendPlacesMapOptions)', 'initBackendPlacesMap(window.backendPlacesMapOptions)', $content);

    if (!preg_match('/maps-helper\.js[\s\S]{0,800}function initMap\(\)[\s\S]{0,800}callback=initMap/', $content)) {
        $content = preg_replace(
            '/(<script src="\{\{ asset\(\'assets\/js\/maps-helper\.js\'\) \}\}\?v=20260521pinfix"><\/script>\s*)<script type="text\/javascript" src="https:\/\/maps\.googleapis\.com\/maps\/api\/js[^<]+<\/script>\s*(<script>)/',
            '$1$2',
            $content,
            1,
            $count
        );
        if ($count) {
            $content = preg_replace(
                '/(\n\s*function initMap\(\) \{[\s\S]*?\n\s*\})\s*\n(\s*\$\(document\.ready|\s*\/\/|\s*var )/',
                "$1\n    </script>\n    <script type=\"text/javascript\" src=\"https://maps.googleapis.com/maps/api/js?key={{ env('MAP_API_KEY') }}&libraries=marker,places&loading=async&callback=initMap\" async defer></script>\n    <script>\n$2",
                $content,
                1
            );
        }
    }

    file_put_contents($file, $content);
    echo "OK: {$file}\n";
}

echo "DONE\n";
