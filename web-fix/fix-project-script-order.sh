#!/bin/bash
for f in /www/wwwroot/admin-homes/resources/views/project/create.blade.php /www/wwwroot/admin-homes/resources/views/project/edit.blade.php; do
  python3 <<'PY' "$f"
import sys
from pathlib import Path
path = Path(sys.argv[1])
text = path.read_text(encoding='utf-8')
old = """    <script type=\"text/javascript\"
        src=\"https://maps.googleapis.com/maps/api/js?key={{ env('MAP_API_KEY') }}&libraries=marker,places&loading=async&callback=initMap\"
        async defer></script>
    <script src=\"{{ asset('assets/js/maps-helper.js') }}\"></script>"""
new = """    <script src=\"{{ asset('assets/js/maps-helper.js') }}?v=20260521\"></script>
    <script type=\"text/javascript\"
        src=\"https://maps.googleapis.com/maps/api/js?key={{ env('MAP_API_KEY') }}&libraries=marker,places&loading=async&callback=initMap\"
        async defer></script>"""
if old not in text:
    print(path, 'header already fixed or pattern missing')
else:
    path.write_text(text.replace(old, new, 1), encoding='utf-8')
    print(path, 'header fixed')
PY
done
