/* Backend-powered Google Maps/Places helper
   - Loads map with a domain-restricted MAP API key (already on the page)
   - Uses backend endpoints with IP-restricted PLACE API key for:
     - Autocomplete:  GET /api/get-map-places-list?input=...
     - Details/Geocode: GET /api/get-map-place-details?place_id=... OR ?latitude=..&longitude=..
*/
(function (global) {
    function parseCoord(value, fallback) {
        var parsed = parseFloat(String(value == null ? '' : value).trim());
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function readFieldValue(selector) {
        if (!selector) return '';
        var el = document.querySelector(selector);
        return el ? String(el.value || '').trim() : '';
    }

    function toLatLngLiteral(lat, lng) {
        return {
            lat: parseCoord(lat, 0),
            lng: parseCoord(lng, 0)
        };
    }

    function extractGeometryLatLng(result, fallbackLat, fallbackLng) {
        var geo = result && result.geometry ? result.geometry.location : null;
        if (!geo) {
            return toLatLngLiteral(fallbackLat, fallbackLng);
        }

        var lat = typeof geo.lat === 'function' ? geo.lat() : geo.lat;
        var lng = typeof geo.lng === 'function' ? geo.lng() : geo.lng;
        if (!Number.isFinite(parseFloat(lat)) || !Number.isFinite(parseFloat(lng))) {
            return toLatLngLiteral(fallbackLat, fallbackLng);
        }

        return toLatLngLiteral(lat, lng);
    }

    function setMapMarkerPosition(map, marker, lat, lng) {
        if (!map || !marker) {
            return null;
        }

        var position = toLatLngLiteral(lat, lng);
        map.setCenter(position);
        map.setZoom(17);
        marker.position = position;
        marker.map = map;
        return position;
    }

    function getOrCreateLoader(mapElement) {
        var existing = mapElement.querySelector('.map-loading-overlay');
        if (existing) { return existing; }
        var overlay = document.createElement('div');
        overlay.className = 'map-loading-overlay';
        overlay.style.position = 'absolute';
        overlay.style.inset = '0px';
        overlay.style.background = 'rgba(255,255,255,0.6)';
        overlay.style.display = 'none';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.style.zIndex = '2001';
        var text = document.createElement('div');
        text.style.padding = '8px 12px';
        text.style.background = '#fff';
        text.style.border = '1px solid #ddd';
        text.style.borderRadius = '4px';
        text.style.boxShadow = '0 2px 6px rgba(0,0,0,0.15)';
        text.style.color = '#333';
        text.style.fontSize = '13px';
        text.textContent = 'Loading...';
        overlay.appendChild(text);
        // Ensure container is positioned
        var computedStyle = window.getComputedStyle(mapElement);
        if (computedStyle.position === 'static') {
            mapElement.style.position = 'relative';
        }
        mapElement.appendChild(overlay);
        return overlay;
    }

    function showLoader(map) {
        var el = (map && map.getDiv) ? map.getDiv() : map;
        if (!el) return;
        var overlay = getOrCreateLoader(el);
        overlay.style.display = 'flex';
    }

    function hideLoader(map) {
        var el = (map && map.getDiv) ? map.getDiv() : map;
        if (!el) return;
        var overlay = el.querySelector('.map-loading-overlay');
        if (overlay) { overlay.style.display = 'none'; }
    }

    function createSuggestionsContainer($input) {
        var $existing = $input.next('#places-suggestions');
        if ($existing.length) { return $existing; }
        var $suggestions = $('<div id="places-suggestions" class="list-group" style="position:absolute; z-index: 2000; width: 100%;"></div>');
        $input.after($suggestions); // positioned in normal flow; page should handle layout
        return $suggestions;
    }

    function setFromGeocodeResult(result, latLngObj, selectors, map, marker) {
        var address_components = (result && result.address_components) ? result.address_components : [];
        var city, state, country, full_address;

        for (var i = 0; i < address_components.length; i++) {
            var types = address_components[i].types || [];

            if (types.indexOf('locality') !== -1) {
                city = address_components[i].long_name;
            }
            else if (types.indexOf('administrative_area_level_2') !== -1 && !city) {
                // fallback when locality is missing
                city = address_components[i].long_name;
            }
            else if (types.indexOf('administrative_area_level_3') !== -1 && !city) {
                // secondary fallback
                city = address_components[i].long_name;
            }
            else if (types.indexOf('administrative_area_level_1') !== -1) {
                state = address_components[i].long_name;
            }
            else if (types.indexOf('country') !== -1) {
                country = address_components[i].long_name;
            }
        }

        full_address = result && result.formatted_address ? result.formatted_address : '';

        if (selectors.inputSelector) { $(selectors.inputSelector).val(city || ''); }
        if (selectors.citySelector) { $(selectors.citySelector).val(city || ''); }
        if (selectors.countrySelector) { $(selectors.countrySelector).val(country || ''); }
        if (selectors.stateSelector) { $(selectors.stateSelector).val(state || ''); }
        if (selectors.addressSelector) { $(selectors.addressSelector).val(full_address || ''); }
        if (selectors.latitudeSelector) { $(selectors.latitudeSelector).val(latLngObj.lat); }
        if (selectors.longitudeSelector) { $(selectors.longitudeSelector).val(latLngObj.lng); }

        setMapMarkerPosition(map, marker, latLngObj.lat, latLngObj.lng);
    }

    function fetchPlaceDetails(params) {
        return $.get('/api/get-map-place-details', params);
    }

    function attachAutocomplete($input, selectors, map, marker) {
        var $suggestions = createSuggestionsContainer($input);
        var debounceTimer;
        $input.on('input', function () {
            clearTimeout(debounceTimer);
            var q = $(this).val();
            if (!q || q.length < 3) { $suggestions.empty().hide(); return; }
            debounceTimer = setTimeout(function () {
                showLoader(map);
                $.get('/api/get-map-places-list', { input: q })
                    .done(function (resp) {
                        var data = resp && resp.data ? resp.data : {};
                        var preds = data.predictions || [];
                        $suggestions.empty();
                        preds.slice(0, 7).forEach(function (p) {
                            var text = p.description || (p.structured_formatting && p.structured_formatting.main_text) || '';
                            var $item = $('<a href="#" class="list-group-item list-group-item-action"></a>');
                            $item.text(text);
                            $item.on('click', function (e) {
                                e.preventDefault();
                                $suggestions.empty().hide();
                                if (p.place_id) {
                                    showLoader(map);
                                    fetchPlaceDetails({ place_id: p.place_id }).done(function (r) {
                                        var d = r && r.data ? r.data : {};
                                        var results = d.result ? [d.result] : (d.results || []);
                                        if (results.length) {
                                            var geo = (results[0].geometry && results[0].geometry.location) || {};
                                            if (typeof geo.lat === 'number' && typeof geo.lng === 'number') {
                                                setFromGeocodeResult(results[0], { lat: geo.lat, lng: geo.lng }, selectors, map, marker);
                                            }
                                        }
                                    }).always(function () { hideLoader(map); });
                                }
                            });
                            $suggestions.append($item);
                        });
                        if (preds.length) { $suggestions.show(); } else { $suggestions.hide(); }
                    }).always(function () { hideLoader(map); });
            }, 300);
        });
    }

    function initBackendPlacesMap(options) {
        if (typeof google === 'undefined' || !google.maps || !google.maps.Map) {
            console.warn('Google Maps API is not loaded yet.');
            return null;
        }

        var selectors = options || {};
        var defaultLat = parseCoord(
            readFieldValue(selectors.defaultLatitudeSelector || '#default-latitude')
                || readFieldValue(selectors.latitudeSelector || '#latitude'),
            25.7506
        );
        var defaultLng = parseCoord(
            readFieldValue(selectors.defaultLongitudeSelector || '#default-longitude')
                || readFieldValue(selectors.longitudeSelector || '#longitude'),
            71.3930
        );

        var mapEl = document.getElementById(selectors.mapElementId || 'map');
        if (!mapEl) {
            console.warn('Map element not found:', selectors.mapElementId || 'map');
            return null;
        }

        var mapId = (typeof GOOGLE_MAP_ID !== 'undefined' && GOOGLE_MAP_ID) ? GOOGLE_MAP_ID : 'DEMO_MAP_ID';
        var map = new google.maps.Map(mapEl, {
            mapId: mapId, // Required for AdvancedMarkerElement
            center: { lat: defaultLat, lng: defaultLng },
            zoom: 15
        });
        var marker = new google.maps.marker.AdvancedMarkerElement({
            gmpDraggable: true,
            position: { lat: defaultLat, lng: defaultLng },
            map: map
        });

        // Marker drag → reverse geocode via backend
        google.maps.event.addListener(marker, 'dragend', function (event) {
            var lat = event.latLng.lat();
            var lng = event.latLng.lng();
            showLoader(map);
            fetchPlaceDetails({ latitude: lat, longitude: lng })
                .done(function (resp) {
                    var d = resp && resp.data ? resp.data : {};
                    var results = d.result ? [d.result] : (d.results || []);
                    if (results.length) {
                        setFromGeocodeResult(results[0], { lat: lat, lng: lng }, selectors, map, marker);
                    } else {
                        if (selectors.latitudeSelector) { $(selectors.latitudeSelector).val(lat); }
                        if (selectors.longitudeSelector) { $(selectors.longitudeSelector).val(lng); }
                        setMapMarkerPosition(map, marker, lat, lng);
                    }
                }).always(function () { hideLoader(map); });
        });

        // Text input → backend autocomplete + details
        if (selectors.inputSelector) {
            attachAutocomplete($(selectors.inputSelector), selectors, map, marker);
        }

        global.__backendPlacesMapInstance = {
            map: map,
            marker: marker,
            selectors: selectors
        };

        return { map: map, marker: marker };
    }

    function moveBackendPlacesMapPin(lat, lng, selectors) {
        var inst = global.__backendPlacesMapInstance;
        var map = inst && inst.map;
        var marker = inst && inst.marker;
        var sel = selectors || (inst && inst.selectors) || {};

        if (sel.latitudeSelector) { $(sel.latitudeSelector).val(lat); }
        if (sel.longitudeSelector) { $(sel.longitudeSelector).val(lng); }

        setMapMarkerPosition(map, marker, lat, lng);
    }

    function applyBackendPlaceFromCoords(lat, lng, selectors, onApplied) {
        var inst = global.__backendPlacesMapInstance;
        var map = inst && inst.map;
        var marker = inst && inst.marker;
        var sel = selectors || (inst && inst.selectors) || {};

        moveBackendPlacesMapPin(lat, lng, sel);

        return fetchPlaceDetails({ latitude: lat, longitude: lng })
            .done(function (resp) {
                var d = resp && resp.data ? resp.data : {};
                var results = d.result ? [d.result] : (d.results || []);
                if (results.length) {
                    var resolved = extractGeometryLatLng(results[0], lat, lng);
                    if (map && marker) {
                        setFromGeocodeResult(results[0], resolved, sel, map, marker);
                    } else {
                        var result = results[0];
                        if (sel.latitudeSelector) { $(sel.latitudeSelector).val(resolved.lat); }
                        if (sel.longitudeSelector) { $(sel.longitudeSelector).val(resolved.lng); }
                        if (sel.addressSelector && result.formatted_address) {
                            $(sel.addressSelector).val(result.formatted_address);
                        }
                    }
                    if (typeof onApplied === 'function') {
                        onApplied(results[0], resolved);
                    }
                    return;
                }

                moveBackendPlacesMapPin(lat, lng, sel);
                if (typeof onApplied === 'function') {
                    onApplied(null, toLatLngLiteral(lat, lng));
                }
            })
            .fail(function () {
                moveBackendPlacesMapPin(lat, lng, sel);
                if (typeof onApplied === 'function') {
                    onApplied(null, toLatLngLiteral(lat, lng));
                }
            });
    }

    global.initBackendPlacesMap = initBackendPlacesMap;
    global.applyBackendPlaceFromCoords = applyBackendPlaceFromCoords;
    global.moveBackendPlacesMapPin = moveBackendPlacesMapPin;
})(window);


