from pathlib import Path

old = """    <script type=\"text/javascript\"
        src=\"https://maps.googleapis.com/maps/api/js?key={{ env('MAP_API_KEY') }}&libraries=marker,places&loading=async&callback=initMap\"
        async defer></script>
    <script src=\"{{ asset('assets/js/maps-helper.js') }}\"></script>"""

new = """    <script src=\"{{ asset('assets/js/maps-helper.js') }}?v=20260521\"></script>
    <script type=\"text/javascript\"
        src=\"https://maps.googleapis.com/maps/api/js?key={{ env('MAP_API_KEY') }}&libraries=marker,places&loading=async&callback=initMap\"
        async defer></script>"""

for path in [
    "/www/wwwroot/admin-homes/resources/views/project/create.blade.php",
    "/www/wwwroot/admin-homes/resources/views/project/edit.blade.php",
]:
    p = Path(path)
    text = p.read_text(encoding="utf-8")
    if old in text:
        p.write_text(text.replace(old, new, 1), encoding="utf-8")
        print(path, "fixed")
    else:
        print(path, "skip")
