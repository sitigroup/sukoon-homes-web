<?php

$files = [
    '/www/wwwroot/admin-homes/resources/views/property/edit.blade.php',
    '/www/wwwroot/admin-homes/resources/views/property/create.blade.php',
    '/www/wwwroot/admin-homes/resources/views/project/edit.blade.php',
    '/www/wwwroot/admin-homes/resources/views/project/create.blade.php',
];

$oldBlock = <<<'BLADE'
    <script src="{{ asset('assets/js/maps-helper.js') }}?v=20260521area2"></script>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key={{ env('MAP_API_KEY') }}&libraries=marker,places&loading=async&callback=initMap" async defer></script>
    <script>
        var backendPlacesMapInitDone = false;
        var backendPlacesMapOptions = {
BLADE;

$newBlock = <<<'BLADE'
    <script src="{{ asset('assets/js/maps-helper.js') }}?v=20260521pinfix"></script>
    <script>
        var backendPlacesMapInitDone = false;
        window.backendPlacesMapOptions = {
BLADE;

$closeInsert = <<<'BLADE'
        }
    </script>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key={{ env('MAP_API_KEY') }}&libraries=marker,places&loading=async&callback=initMap" async defer></script>
    <script>
BLADE;

foreach ($files as $file) {
    if (!is_file($file)) {
        echo "SKIP missing {$file}\n";
        continue;
    }
    $content = file_get_contents($file);

    if (str_contains($content, '20260521pinfix') && str_contains($content, 'window.backendPlacesMapOptions')) {
        echo "SKIP already patched: {$file}\n";
        continue;
    }

    if (str_contains($content, $oldBlock)) {
        $content = str_replace($oldBlock, $newBlock, $content);
    } else {
        $content = preg_replace(
            '/maps-helper\.js\)\s*\}\}\?v=[^"]+"/',
            "maps-helper.js') }}?v=20260521pinfix\"",
            $content,
            1
        );
        $content = preg_replace(
            '/var backendPlacesMapOptions = \{/',
            'window.backendPlacesMapOptions = {',
            $content,
            1
        );
        $content = preg_replace(
            '/(window\.backendPlacesMapOptions = \{[\s\S]*?\n        \});\s*\n        function initMap\(\)/',
            "$1;\n        window.__backendPlacesMapPendingOptions = window.backendPlacesMapOptions;\n        function initMap()",
            $content,
            1
        );
        $content = preg_replace(
            '/initBackendPlacesMap\(backendPlacesMapOptions\)/',
            'initBackendPlacesMap(window.backendPlacesMapOptions)',
            $content,
            1
        );
        if (!str_contains($content, 'callback=initMap" async defer></script>')) {
            echo "FAIL pattern: {$file}\n";
            continue;
        }
        if (!preg_match('/maps-helper[\s\S]{0,400}callback=initMap/', $content)) {
            $content = preg_replace(
                '/(\n        function initMap\(\) \{[\s\S]*?\n        \})\s*\n\n        \$\(document\.ready/',
                "$1\n    </script>\n    <script type=\"text/javascript\" src=\"https://maps.googleapis.com/maps/api/js?key={{ env('MAP_API_KEY') }}&libraries=marker,places&loading=async&callback=initMap\" async defer></script>\n    <script>\n\n        \$(document.ready",
                $content,
                1
            );
        }
    }

    if (!str_contains($content, 'window.__backendPlacesMapPendingOptions')) {
        $content = str_replace(
            "window.backendPlacesMapOptions = {",
            "window.backendPlacesMapOptions = {",
            $content
        );
        $content = preg_replace(
            '/(window\.backendPlacesMapOptions = \{[\s\S]*?\n        \});/',
            "$1;\n        window.__backendPlacesMapPendingOptions = window.backendPlacesMapOptions",
            $content,
            1
        );
    }

    file_put_contents($file, $content);
    echo "OK: {$file}\n";
}

echo "DONE\n";
