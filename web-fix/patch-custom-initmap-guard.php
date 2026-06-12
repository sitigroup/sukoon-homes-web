<?php

$path = '/www/wwwroot/admin-homes/public/assets/js/custom/custom.js';
$content = file_get_contents($path);

$needle = "function initMap() {\n    var latitude = parseFloat(\$('#latitude').val());";
$guard = <<<'JS'
function initMap() {
    if (document.getElementById('map') && typeof window.initBackendPlacesMap === 'function') {
        var opts = window.backendPlacesMapOptions || window.__backendPlacesMapPendingOptions;
        if (opts) {
            return window.initBackendPlacesMap(opts);
        }
    }

    var latitude = parseFloat($('#latitude').val());
JS;

if (str_contains($content, 'window.initBackendPlacesMap === \'function\'')) {
    echo "SKIP: custom.js guard already present\n";
    exit(0);
}

if (!str_contains($content, $needle)) {
    echo "FAIL: initMap anchor not found\n";
    exit(1);
}

$content = str_replace($needle, $guard, $content);
file_put_contents($path, $content);
echo "OK: custom.js initMap guard added\n";
