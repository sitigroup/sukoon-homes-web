/* Backend-powered Google Maps/Places helper */
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

    function readMarkerCoords(marker) {
        if (!marker) return null;

        var pos = marker.position;
        if (!pos && typeof marker.getPosition === 'function') {
            pos = marker.getPosition();
        }
        if (!pos) return null;

        return toLatLngLiteral(
            typeof pos.lat === 'function' ? pos.lat() : pos.lat,
            typeof pos.lng === 'function' ? pos.lng() : pos.lng
        );
    }

    function setMapMarkerPosition(map, marker, lat, lng) {
        if (!map || !marker) {
            return null;
        }

        var position = toLatLngLiteral(lat, lng);
        if (typeof map.panTo === 'function') {
            map.panTo(position);
        } else {
            map.setCenter(position);
        }
        map.setZoom(17);

        if (typeof marker.setPosition === 'function') {
            marker.setPosition(position);
        } else {
            marker.position = position;
        }
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
        $input.after($suggestions);
        return $suggestions;
    }

    function hideLocationSuggestions(selectors) {
        $('#places-suggestions').hide().empty();
        $('.pac-container').hide();
        if (selectors && selectors.inputSelector) {
            $(selectors.inputSelector).blur();
        }
    }

    function setFromGeocodeResult(result, latLngObj, selectors, map, marker) {
        return invokeOnPinMoveComplete(result, latLngObj, selectors, map, marker, { source: 'autocomplete' });
    }

    function setFromGeocodeResultFieldsOnly(result, latLngObj, selectors) {
        var address_components = (result && result.address_components) ? result.address_components : [];
        var city, state, country, full_address;

        for (var i = 0; i < address_components.length; i++) {
            var types = address_components[i].types || [];

            if (types.indexOf('locality') !== -1) {
                city = address_components[i].long_name;
            }
            else if (types.indexOf('administrative_area_level_2') !== -1 && !city) {
                city = address_components[i].long_name;
            }
            else if (types.indexOf('administrative_area_level_3') !== -1 && !city) {
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
    }

    function invokeOnPinMoveComplete(result, latLngObj, selectors, map, marker, options) {
        if (map && marker) {
            setMapMarkerPosition(map, marker, latLngObj.lat, latLngObj.lng);
        }
        if (selectors.latitudeSelector) { $(selectors.latitudeSelector).val(latLngObj.lat); }
        if (selectors.longitudeSelector) { $(selectors.longitudeSelector).val(latLngObj.lng); }
        hideLocationSuggestions(selectors);

        if (typeof global.onPinMoveComplete === 'function') {
            return global.onPinMoveComplete(result, latLngObj.lat, latLngObj.lng, options || {});
        }

        setFromGeocodeResultFieldsOnly(result, latLngObj, selectors);
        return Promise.resolve(result);
    }

    function fetchPlaceDetails(params) {
        return $.get('/api/get-map-place-details', params);
    }

    function applyCoordsOnly(coords, selectors, map, marker) {
        if (selectors.latitudeSelector) { $(selectors.latitudeSelector).val(coords.lat); }
        if (selectors.longitudeSelector) { $(selectors.longitudeSelector).val(coords.lng); }
        setMapMarkerPosition(map, marker, coords.lat, coords.lng);
    }

    function fallbackClientGeocode(coords, selectors, map, marker) {
        if (global.__areaListingPinMoving) {
            return;
        }
        global.__areaListingPinMoving = true;
        runPinMoveGeocode(coords, selectors, map, marker);
    }

    function finishPinMove(selectors, map) {
        global.__areaListingPinMoving = false;
        hideLocationSuggestions(selectors);
        hideLoader(map);
    }

    function runPinMoveGeocode(coords, selectors, map, marker) {
        showLoader(map);

        var handleResult = function (result, resolved) {
            var chain = invokeOnPinMoveComplete(result, resolved, selectors, map, marker, { source: 'pin' });
            if (chain && typeof chain.then === 'function') {
                return chain.finally(function () {
                    finishPinMove(selectors, map);
                });
            }
            finishPinMove(selectors, map);
            return chain;
        };

        return fetchPlaceDetails({ latitude: coords.lat, longitude: coords.lng })
            .then(function (resp) {
                var d = resp && resp.data ? resp.data : {};
                var results = d.result ? [d.result] : (d.results || []);
                if (results.length) {
                    var resolved = extractGeometryLatLng(results[0], coords.lat, coords.lng);
                    return handleResult(results[0], resolved);
                }

                if (typeof google === 'undefined' || !google.maps || !google.maps.Geocoder) {
                    return handleResult(null, toLatLngLiteral(coords.lat, coords.lng));
                }

                var geocoder = new google.maps.Geocoder();
                return new Promise(function (resolve) {
                    geocoder.geocode({ location: toLatLngLiteral(coords.lat, coords.lng) }, function (gResults, status) {
                        if (status === 'OK' && gResults && gResults.length) {
                            var gResolved = extractGeometryLatLng(gResults[0], coords.lat, coords.lng);
                            resolve(handleResult(gResults[0], gResolved));
                            return;
                        }
                        resolve(handleResult(null, toLatLngLiteral(coords.lat, coords.lng)));
                    });
                });
            })
            .catch(function () {
                finishPinMove(selectors, map);
            });
    }

    function attachMarkerDragHandler(marker, selectors, map) {
        var onDragEnd = function () {
            var coords = readMarkerCoords(marker);
            if (!coords || global.__areaListingPinMoving) {
                return;
            }

            hideLocationSuggestions(selectors);
            global.__areaListingPinMoving = true;
            runPinMoveGeocode(coords, selectors, map, marker);
        };

        if (typeof marker.addListener === 'function') {
            marker.addListener('dragend', onDragEnd);
            marker.addListener('gmp-dragend', onDragEnd);
        } else if (google.maps && google.maps.event) {
            google.maps.event.addListener(marker, 'dragend', onDragEnd);
        }
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
                                            var resolved = extractGeometryLatLng(results[0], 0, 0);
                                            invokeOnPinMoveComplete(results[0], resolved, selectors, map, marker, { source: 'autocomplete' });
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

    async function createMapMarker(map, defaultLat, defaultLng) {
        var position = { lat: defaultLat, lng: defaultLng };

        try {
            if (google.maps.importLibrary) {
                var markerLib = await google.maps.importLibrary('marker');
                if (markerLib && markerLib.AdvancedMarkerElement) {
                    return new markerLib.AdvancedMarkerElement({
                        gmpDraggable: true,
                        position: position,
                        map: map
                    });
                }
            }
        } catch (error) {
            console.warn('Advanced marker unavailable, using classic marker.', error);
        }

        if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
            return new google.maps.marker.AdvancedMarkerElement({
                gmpDraggable: true,
                position: position,
                map: map
            });
        }

        return new google.maps.Marker({
            position: position,
            map: map,
            draggable: true
        });
    }

    async function bootBackendPlacesMap(options) {
        if (global.__backendPlacesMapInstance) {
            return global.__backendPlacesMapInstance;
        }

        if (typeof google === 'undefined' || !google.maps) {
            console.warn('Google Maps API is not loaded yet.');
            return null;
        }

        var selectors = options || global.__backendPlacesMapPendingOptions || {};
        global.__backendPlacesMapPendingOptions = selectors;

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

        if (google.maps.importLibrary) {
            await google.maps.importLibrary('maps');
        }

        var mapId = (typeof GOOGLE_MAP_ID !== 'undefined' && GOOGLE_MAP_ID) ? GOOGLE_MAP_ID : 'DEMO_MAP_ID';
        var map = new google.maps.Map(mapEl, {
            mapId: mapId,
            center: { lat: defaultLat, lng: defaultLng },
            zoom: 15
        });

        var marker = await createMapMarker(map, defaultLat, defaultLng);
        attachMarkerDragHandler(marker, selectors, map);

        if (selectors.inputSelector) {
            attachAutocomplete($(selectors.inputSelector), selectors, map, marker);
        }

        global.__backendPlacesMapInstance = {
            map: map,
            marker: marker,
            selectors: selectors
        };

        return global.__backendPlacesMapInstance;
    }

    function initBackendPlacesMap(options) {
        global.__backendPlacesMapPendingOptions = options || global.__backendPlacesMapPendingOptions || {};

        if (global.__backendPlacesMapInstance) {
            return Promise.resolve(global.__backendPlacesMapInstance);
        }

        if (!global.__backendPlacesMapBootPromise) {
            global.__backendPlacesMapBootPromise = bootBackendPlacesMap(global.__backendPlacesMapPendingOptions)
                .catch(function (error) {
                    console.error('Failed to initialize backend places map.', error);
                    return null;
                })
                .finally(function () {
                    global.__backendPlacesMapBootPromise = null;
                });
        }

        return global.__backendPlacesMapBootPromise;
    }

    function ensureBackendPlacesMapReady(selectors) {
        if (global.__backendPlacesMapInstance) {
            return Promise.resolve(global.__backendPlacesMapInstance);
        }

        var opts = selectors || global.__backendPlacesMapPendingOptions;
        if (!opts) {
            return Promise.resolve(null);
        }

        return initBackendPlacesMap(opts);
    }

    function moveBackendPlacesMapPin(lat, lng, selectors) {
        var sel = selectors || (global.__backendPlacesMapPendingOptions || {});

        return ensureBackendPlacesMapReady(sel).then(function (inst) {
            if (!inst) {
                return null;
            }

            if (sel.latitudeSelector) { $(sel.latitudeSelector).val(lat); }
            if (sel.longitudeSelector) { $(sel.longitudeSelector).val(lng); }

            return setMapMarkerPosition(inst.map, inst.marker, lat, lng);
        });
    }

    function applyBackendPlaceFromCoords(lat, lng, selectors, onApplied) {
        var sel = selectors || (global.__backendPlacesMapPendingOptions || {});
        var hasCustomApply = typeof onApplied === 'function';

        return ensureBackendPlacesMapReady(sel).then(function (inst) {
            return fetchPlaceDetails({ latitude: lat, longitude: lng }).then(function (resp) {
                var d = resp && resp.data ? resp.data : {};
                var results = d.result ? [d.result] : (d.results || []);
                var map = inst && inst.map;
                var marker = inst && inst.marker;
                var resolved = toLatLngLiteral(lat, lng);

                if (results.length) {
                    resolved = extractGeometryLatLng(results[0], lat, lng);

                    if (map && marker) {
                        setMapMarkerPosition(map, marker, resolved.lat, resolved.lng);
                    }
                    if (sel.latitudeSelector) { $(sel.latitudeSelector).val(resolved.lat); }
                    if (sel.longitudeSelector) { $(sel.longitudeSelector).val(resolved.lng); }

                    if (hasCustomApply) {
                        var applied = onApplied(results[0], resolved);
                        if (applied && typeof applied.then === 'function') {
                            return applied.then(function () { return results[0]; });
                        }
                        return results[0];
                    }

                    return invokeOnPinMoveComplete(results[0], resolved, sel, map, marker, { source: 'coords' }).then(function () {
                        return results[0];
                    });
                }

                if (hasCustomApply) {
                    var emptyApplied = onApplied(null, resolved);
                    if (emptyApplied && typeof emptyApplied.then === 'function') {
                        return emptyApplied.then(function () { return null; });
                    }
                } else {
                    return moveBackendPlacesMapPin(lat, lng, sel).then(function () { return null; });
                }
                return null;
            }, function () {
                if (hasCustomApply) {
                    var failedApplied = onApplied(null, toLatLngLiteral(lat, lng));
                    if (failedApplied && typeof failedApplied.then === 'function') {
                        return failedApplied.then(function () { return null; });
                    }
                    return null;
                }
                return moveBackendPlacesMapPin(lat, lng, sel).then(function () { return null; });
            });
        });
    }

    global.initBackendPlacesMap = initBackendPlacesMap;
    global.ensureBackendPlacesMapReady = ensureBackendPlacesMapReady;
    global.applyBackendPlaceFromCoords = applyBackendPlaceFromCoords;
    global.moveBackendPlacesMapPin = moveBackendPlacesMapPin;
})(window);
