#!/usr/bin/env python3
import re
from pathlib import Path

INIT_BLOCK = """        var backendPlacesMapInitDone = false;
        var backendPlacesMapOptions = {
            mapElementId: 'map',
            inputSelector: '#searchInput',
            citySelector: '#city',
            countrySelector: '#country',
            stateSelector: '#state',
            addressSelector: '#address',
            latitudeSelector: '#latitude',
            longitudeSelector: '#longitude',
            defaultLatitudeSelector: '#default-latitude',
            defaultLongitudeSelector: '#default-longitude'
        };
        function initMap() {
            if (backendPlacesMapInitDone) return;
            if (typeof google === 'undefined' || !google.maps) return;
            if (typeof window.initBackendPlacesMap !== 'function') {
                setTimeout(initMap, 50);
                return;
            }
            window.initBackendPlacesMap(backendPlacesMapOptions).then(function (inst) {
                if (inst) {
                    backendPlacesMapInitDone = true;
                } else {
                    setTimeout(initMap, 100);
                }
            });
        }"""

OLD_INIT_RE = re.compile(
    r"        var backendPlacesMapInitDone = false;\s*"
    r"function initMap\(\) \{.*?\n        \}",
    re.S,
)

SCRIPT_HEADER = """    <script src="{{ asset('assets/js/maps-helper.js') }}?v=20260521"></script>
    <script type="text/javascript"
        src="https://maps.googleapis.com/maps/api/js?key={{ env('MAP_API_KEY') }}&libraries=marker,places&loading=async&callback=initMap"
        async defer></script>"""

OLD_HEADER_RE = re.compile(
    r"    <script type=\"text/javascript\"\s*"
    r"src=\"https://maps\.googleapis\.com/maps/api/js\?key=\{\{ env\('MAP_API_KEY'\) \}\}&libraries=marker,places&loading=async&callback=initMap\"\s*"
    r"async defer></script>\s*"
    r"<script src=\"\{\{ asset\('assets/js/maps-helper\.js'\) \}\}\}\"></script>",
    re.S,
)

for path in [
    "/www/wwwroot/admin-homes/resources/views/project/create.blade.php",
    "/www/wwwroot/admin-homes/resources/views/project/edit.blade.php",
]:
    p = Path(path)
    text = p.read_text(encoding="utf-8")
    text, n1 = OLD_HEADER_RE.subn(SCRIPT_HEADER, text, count=1)
    text, n2 = OLD_INIT_RE.subn(INIT_BLOCK, text, count=1)
    p.write_text(text, encoding="utf-8")
    print(path, "header", n1, "init", n2)
