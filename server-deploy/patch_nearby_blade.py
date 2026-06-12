#!/usr/bin/env python3
"""Insert Travel Time settings fields into Nearby Places admin blade (run on server)."""

from pathlib import Path

PATH = Path("/www/wwwroot/admin-homes/app/Plugins/NearbyPlaces/views/admin/index.blade.php")

INSERT = """                <div class="col-md-3">
                    <label class="form-label">{{ __('Travel time (driving)') }}</label>
                    <select name="nearby_places_travel_time_enabled" class="form-control">
                        <option value="1" @selected(($settings['nearby_places_travel_time_enabled'] ?? '0') === '1')>{{ __('Yes') }}</option>
                        <option value="0" @selected(($settings['nearby_places_travel_time_enabled'] ?? '0') === '0')>{{ __('No') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Travel cache TTL (hours)') }}</label>
                    <input type="number" name="nearby_places_travel_cache_ttl_hours" class="form-control" min="1" max="720" value="{{ $settings['nearby_places_travel_cache_ttl_hours'] ?? 168 }}">
                </div>
"""

def main() -> None:
    text = PATH.read_text(encoding="utf-8")
    needle = '<div class="col-12">\n                    <button type="submit"'
    if needle not in text:
        raise SystemExit(f"Needle not found in {PATH}")
    if "nearby_places_travel_time_enabled" in text:
        print("Blade already patched; skipping.")
        return
    text = text.replace(needle, INSERT + needle, 1)
    PATH.write_text(text, encoding="utf-8")
    print("Blade patched OK.")

if __name__ == "__main__":
    main()
