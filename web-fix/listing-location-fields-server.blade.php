@php
    $areaListingStates = \App\Plugins\AreaListing\Models\State::where('status', 1)->orderBy('sort_order')->orderBy('name')->get();
    $areaListingCities = \App\Plugins\AreaListing\Models\City::where('status', 1)->orderBy('sort_order')->orderBy('name')->get();
    $areaListingAreas = \App\Plugins\AreaListing\Models\Area::with(['subAreas' => function ($query) {
        $query->where('status', 1)->orderBy('sort_order')->orderBy('name');
    }])->where('status', 1)->orderBy('city')->orderBy('sort_order')->orderBy('name')->get();

    $areaListingTable = ($listingType ?? '') === 'project' ? 'area_listing_project_locations' : 'area_listing_property_locations';
    $areaListingKey = ($listingType ?? '') === 'project' ? 'project_id' : 'property_id';
    $areaListingCurrent = !empty($listingId ?? null)
        ? \Illuminate\Support\Facades\DB::table($areaListingTable)->where($areaListingKey, $listingId)->first()
        : null;
    $selectedStateId = old('state_id', $areaListingCurrent->state_id ?? '');
    $selectedCityId = old('city_id', $areaListingCurrent->city_id ?? '');
    $selectedAreaId = old('area_id', $areaListingCurrent->area_id ?? '');
    $selectedSubAreaId = old('sub_area_id', $areaListingCurrent->sub_area_id ?? '');
    $detectedSubAreaName = old(
        'detected_sub_area_name',
        $areaListingCurrent->detected_sub_area_name ?? $areaListingCurrent->sub_area_name ?? ''
    );

    $listingCityName = '';
    $listingStateName = '';
    if (! empty($listingId)) {
        $listingRowTable = ($listingType ?? '') === 'project' ? 'projects' : 'propertys';
        $listingRow = \Illuminate\Support\Facades\DB::table($listingRowTable)->where('id', $listingId)->first(['city', 'state', 'country']);
        $listingCityName = trim((string) ($listingRow->city ?? ''));
        $listingStateName = trim((string) ($listingRow->state ?? ''));
    }

    if ($listingCityName !== '' && $selectedCityId) {
        $storedCity = $areaListingCities->firstWhere('id', (int) $selectedCityId);
        $storedCityName = trim((string) ($storedCity->name ?? ($areaListingCurrent->city ?? '')));
        if ($storedCityName !== '' && strcasecmp($storedCityName, $listingCityName) !== 0) {
            $selectedCityId = '';
            $selectedAreaId = '';
            $selectedSubAreaId = '';
        }
    }

    $detectedAreaName = trim((string) old('detected_area_name', $areaListingCurrent->detected_area_name ?? ''));
    $detectedSubAreaName = trim((string) $detectedSubAreaName);
    $showDetectedAreaInfo = empty($selectedAreaId) && $detectedAreaName !== '';
    $showDetectedSubInfo = empty($selectedSubAreaId) && $detectedSubAreaName !== '';
@endphp

<div class="col-md-12">
    <div class="alert alert-light border mb-3">
        <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center">
            <div>
                <strong>{{ __('Area Wise Location') }}</strong>
                <div class="text-muted small">{{ __('Public users see only Sub-area, Area, City, State. Full address and map stay private.') }}</div>
                <div class="text-muted small area-listing-city-hint mt-1">{{ __('Area and Sub Area use the City, State, and Country fields above.') }}</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary area-listing-current-location">{{ __('Get Current Location') }}</button>
        </div>
    </div>
</div>

<div class="col-md-6 form-group visually-hidden area-listing-internal-state" aria-hidden="true">
    <label class="form-label col-12">{{ __('State') }}</label>
    <select name="state_id" class="form-control area-listing-admin-state area-listing-native-select" tabindex="-1">
        <option value="">{{ __('Select State') }}</option>
        @foreach ($areaListingStates as $state)
            <option value="{{ $state->id }}" data-name="{{ e($state->name) }}" data-country="{{ e($state->country) }}" @selected((string) $selectedStateId === (string) $state->id)>{{ $state->name }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-6 form-group visually-hidden area-listing-internal-city" aria-hidden="true">
    <label class="form-label col-12">{{ __('City') }}</label>
    <select name="city_id" class="form-control area-listing-admin-city area-listing-native-select" tabindex="-1" @disabled(empty($selectedStateId))>
        <option value="">{{ __('Select City') }}</option>
        @foreach ($areaListingCities as $city)
            <option value="{{ $city->id }}" data-state-id="{{ $city->state_id }}" data-name="{{ e($city->name) }}" @selected((string) $selectedCityId === (string) $city->id)>{{ $city->name }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-6 form-group">
    <label class="form-label col-12">{{ __('Area') }}</label>
    <select name="area_id" id="area_listing_area_id_{{ $listingType ?? 'listing' }}" class="form-control select2 area-listing-admin-area" @disabled(empty($selectedCityId))>
        <option value="">{{ __('Select Area') }}</option>
        @foreach ($areaListingAreas as $area)
            <option value="{{ $area->id }}" data-city-id="{{ $area->city_id }}" data-city="{{ e($area->city_name ?: $area->city) }}" data-state="{{ e($area->state) }}" @selected((string) $selectedAreaId === (string) $area->id)>
                {{ $area->city_name ?: $area->city }} - {{ $area->name }}
            </option>
        @endforeach
    </select>
    <div class="area-listing-detected-info alert alert-info py-2 px-3 mt-2 mb-0 small {{ $showDetectedAreaInfo ? '' : 'd-none' }}" role="status">
        <div class="mb-1">
            <span class="text-muted">{{ __('Detected Area') }}:</span>
            <span class="area-listing-detected-area-label fw-semibold">{{ $detectedAreaName }}</span>
            <button type="button" class="btn btn-link btn-sm p-0 ms-1 area-listing-edit-detected-area">{{ __('edit') }}</button>
        </div>
        <div class="area-listing-detected-sub-row {{ ($showDetectedAreaInfo && $showDetectedSubInfo) ? '' : 'd-none' }} mb-1">
            <span class="text-muted">{{ __('Detected Sub Area') }}:</span>
            <span class="area-listing-detected-sub-area-label fw-semibold">{{ $detectedSubAreaName }}</span>
            <button type="button" class="btn btn-link btn-sm p-0 ms-1 area-listing-edit-detected-sub-area">{{ __('edit') }}</button>
        </div>
        <div class="text-muted area-listing-detected-create-note">{{ __('Pending area/sub area names will be created automatically when you save.') }}</div>
    </div>
    <input type="hidden" name="detected_area_name" id="detected_area_name_input" class="area-listing-detected-area" value="{{ old('detected_area_name', $areaListingCurrent->detected_area_name ?? '') }}">
    <input type="hidden" name="area_listing_source" class="area-listing-source" value="{{ old('area_listing_source', $areaListingCurrent->location_source ?? $areaListingCurrent->source ?? 'admin') }}">
    <input type="hidden" name="location_is_verified" class="area-listing-verified" value="{{ old('location_is_verified', $areaListingCurrent->is_verified ?? 0) }}">
    <input type="hidden" name="area_listing_admin_save" value="1">
</div>
<div class="col-md-6 form-group">
    <label class="form-label col-12">{{ __('Sub Area') }}</label>
    <select name="sub_area_id" id="area_listing_sub_area_id_{{ $listingType ?? 'listing' }}" class="form-control select2 area-listing-admin-sub-area" @disabled(empty($selectedAreaId))>
        <option value="">{{ __('Select Sub Area') }}</option>
        @foreach ($areaListingAreas as $area)
            @foreach ($area->subAreas as $subArea)
                <option value="{{ $subArea->id }}" data-area-id="{{ $area->id }}" data-city-id="{{ $area->city_id }}" @selected((string) $selectedSubAreaId === (string) $subArea->id)>
                    {{ $area->name }} - {{ $subArea->name }}
                </option>
            @endforeach
        @endforeach
    </select>
    <input type="hidden" name="detected_sub_area_name" id="detected_sub_area_name_input" class="area-listing-detected-sub-area" value="{{ $detectedSubAreaName }}">
    <div class="area-listing-detected-sub-only-info alert alert-info py-2 px-3 mt-2 mb-0 small {{ $showDetectedSubInfo ? '' : 'd-none' }}" role="status">
        <span class="text-muted">{{ __('Detected Sub Area') }}:</span>
        <span class="area-listing-detected-sub-area-label fw-semibold">{{ $detectedSubAreaName }}</span>
        <button type="button" class="btn btn-link btn-sm p-0 ms-1 area-listing-edit-detected-sub-area">{{ __('edit') }}</button>
        <div class="text-muted mt-1 area-listing-detected-sub-create-note">{{ $showDetectedAreaInfo ? __('This sub area will be created with the area above when you save.') : __('This sub area will be created automatically when you save.') }}</div>
    </div>
</div>

@once
    <style>
        .area-listing-admin-area.select2-hidden-accessible,
        .area-listing-admin-sub-area.select2-hidden-accessible {
            border: 0 !important;
            clip: rect(0 0 0 0) !important;
            height: 1px !important;
            margin: -1px !important;
            overflow: hidden !important;
            padding: 0 !important;
            position: absolute !important;
            width: 1px !important;
        }

        .area-listing-location-select2 .select2-selection--single {
            min-height: 38px;
            border-color: #ced4da;
        }

        .area-listing-location-select2 .select2-selection__rendered {
            line-height: 36px !important;
            padding-left: 12px !important;
        }

        .area-listing-location-select2 .select2-selection__arrow {
            height: 36px !important;
        }

        .area-listing-internal-state .select2-container,
        .area-listing-internal-city .select2-container {
            display: none !important;
        }
    </style>
    <script>
        (function () {
            function bootAreaListingNativeSync() {
                const normalize = function (value) {
                    return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
                };

                const optionMatchesCityNative = function (option, cityId, wantedCity) {
                    if (!option.value) return true;
                    const optionCityId = option.dataset.cityId || '';
                    const optionCity = normalize(option.dataset.city || '');
                    if (cityId) {
                        if (optionCityId) return String(optionCityId) === String(cityId);
                        return !!wantedCity && optionCity === wantedCity;
                    }
                    return !wantedCity || optionCity === wantedCity;
                };

                const optionMatchesAreaNative = function (option, areaId) {
                    if (!option.value) return true;
                    const optionAreaId = option.dataset.areaId || '';
                    if (!areaId) return false;
                    return String(optionAreaId) === String(areaId);
                };

                const titleCaseNative = function (value) {
                    return String(value || '').toLowerCase().replace(/\b\w/g, function (letter) {
                        return letter.toUpperCase();
                    }).trim();
                };

                const readValue = function (root, selectors) {
                    for (const selector of selectors) {
                        const element = root.querySelector(selector) || document.querySelector(selector);
                        if (element && element.value) {
                            return element.value;
                        }
                    }
                    return '';
                };

                const setContainerDisabled = function (select, disabled) {
                    const container = select ? select.nextElementSibling : null;
                    if (!container || !container.classList || !container.classList.contains('select2-container')) return;
                    container.classList.toggle('select2-container--disabled', !!disabled);
                    container.setAttribute('aria-disabled', disabled ? 'true' : 'false');
                };

                const setEmptyText = function (select, text) {
                    if (!select || select.value) return;
                    const rendered = select.nextElementSibling ? select.nextElementSibling.querySelector('.select2-selection__rendered') : null;
                    if (rendered) {
                        rendered.textContent = text;
                        rendered.setAttribute('title', text);
                    }
                };

                const syncMainCityFields = function (root) {
                    const searchInput = root.querySelector('#searchInput, input[placeholder="City"]');
                    const hiddenCity = root.querySelector('#city, input[name="city"]');
                    if (!searchInput || !hiddenCity) return;

                    const searchValue = String(searchInput.value || '').trim();
                    if (searchValue !== '' && hiddenCity.value !== searchValue) {
                        hiddenCity.value = searchValue;
                    }
                };

                const refreshCityHint = function (root) {
                    const hint = root.querySelector('.area-listing-city-hint');
                    if (!hint) return;

                    syncMainCityFields(root);
                    const cityName = titleCaseNative(readValue(root, ['#searchInput', 'input[placeholder="City"]', '#city', 'input[name="city"]']));
                    const stateName = titleCaseNative(readValue(root, ['#state', 'input[name="state"]']));
                    const countryName = titleCaseNative(readValue(root, ['#country', 'input[name="country"]']) || 'India');
                    const parts = [cityName, stateName, countryName].filter(Boolean);

                    hint.textContent = parts.length
                        ? '{{ __('Filtering areas for') }}: ' + parts.join(', ')
                        : '{{ __('Area and Sub Area use the City, State, and Country fields above.') }}';
                };

                const findOption = function (select, label, extraMatch) {
                    const wanted = normalize(label);
                    if (!select || !wanted) return null;
                    return Array.from(select.options).find(function (option) {
                        if (!option.value) return false;
                        const optionLabel = normalize(option.dataset.name || option.textContent);
                        return optionLabel === wanted && (!extraMatch || extraMatch(option));
                    }) || null;
                };

                const syncOne = function (root) {
                    const stateSelect = root.querySelector('.area-listing-admin-state');
                    const citySelect = root.querySelector('.area-listing-admin-city');
                    const areaSelect = root.querySelector('.area-listing-admin-area');
                    const subAreaSelect = root.querySelector('.area-listing-admin-sub-area');
                    if (!stateSelect || !citySelect || !areaSelect || !subAreaSelect) return;

                    const countryInput = root.querySelector('#country, input[name="country"]') || document.querySelector('#country, input[name="country"]');
                    if (countryInput && !countryInput.value) {
                        countryInput.value = 'India';
                    }

                    const stateName = readValue(root, ['#state', 'input[name="state"]']);
                    const cityName = readValue(root, ['#city', 'input[name="city"]', '#searchInput', 'input[placeholder="City"]']);

                    const stateOption = findOption(stateSelect, stateName);
                    if (stateOption && stateSelect.value !== stateOption.value) {
                        stateSelect.value = stateOption.value;
                        stateSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    }

                    const stateId = stateSelect.value || '';
                    Array.from(citySelect.options).forEach(function (option) {
                        const optionStateId = option.dataset.stateId || '';
                        option.disabled = !!optionStateId && !!stateId && optionStateId !== stateId;
                    });

                    const wantedCity = normalize(cityName);
                    const cityOption = findOption(citySelect, cityName, function (option) {
                        const optionStateId = option.dataset.stateId || '';
                        return !optionStateId || !stateId || optionStateId === stateId;
                    });
                    if (cityOption && citySelect.value !== cityOption.value) {
                        citySelect.value = cityOption.value;
                    } else if (wantedCity && (!cityOption || normalize(cityOption.dataset.name || cityOption.textContent || '') !== wantedCity)) {
                        citySelect.value = '';
                    }

                    const cityId = citySelect.value || '';
                    const hasCity = !!cityId || !!wantedCity;
                    citySelect.disabled = !stateId;
                    areaSelect.disabled = !hasCity;

                    if (!hasCity) {
                        if (areaSelect.value) {
                            areaSelect.value = '';
                        }
                        if (subAreaSelect.value) {
                            subAreaSelect.value = '';
                        }
                    }

                    Array.from(areaSelect.options).forEach(function (option) {
                        if (!option.value) {
                            option.disabled = false;
                            return;
                        }
                        option.disabled = !hasCity || !optionMatchesCityNative(option, cityId, wantedCity);
                    });

                    const areaId = areaSelect.value || '';
                    if (areaSelect.value && areaSelect.options[areaSelect.selectedIndex] && areaSelect.options[areaSelect.selectedIndex].disabled) {
                        areaSelect.value = '';
                    }

                    subAreaSelect.disabled = !areaId;
                    Array.from(subAreaSelect.options).forEach(function (option) {
                        if (!option.value) {
                            option.disabled = false;
                            return;
                        }
                        option.disabled = !optionMatchesAreaNative(option, areaId);
                    });
                    if (subAreaSelect.value && subAreaSelect.options[subAreaSelect.selectedIndex] && subAreaSelect.options[subAreaSelect.selectedIndex].disabled) {
                        subAreaSelect.value = '';
                    }

                    setContainerDisabled(citySelect, citySelect.disabled);
                    setContainerDisabled(areaSelect, areaSelect.disabled);
                    setContainerDisabled(subAreaSelect, subAreaSelect.disabled);
                    setEmptyText(stateSelect, 'Select State');
                    setEmptyText(citySelect, 'Select City');
                    setEmptyText(areaSelect, 'Select Area');
                    setEmptyText(subAreaSelect, 'Select Sub Area');
                    refreshCityHint(root);
                };

                const syncAll = function () {
                    if (window.__areaListingGpsSilent || window.__areaListingPinMoving) {
                        return;
                    }
                    document.querySelectorAll('.area-listing-admin-area').forEach(function (areaSelect) {
                        const root = areaSelect.closest('form') || document;
                        syncOne(root);
                    });
                };
                window.syncAll = syncAll;

                ['input', 'change', 'blur'].forEach(function (eventName) {
                    document.addEventListener(eventName, function (event) {
                        if (window.__areaListingPinMoving) {
                            return;
                        }
                        if (!event.target || !event.target.matches || !event.target.matches('#state, input[name="state"], #city, input[name="city"], #searchInput, input[placeholder="City"], #country, input[name="country"]')) return;
                        syncAll();
                    }, true);
                });

                document.addEventListener('click', function (event) {
                    if (event.target && event.target.closest && event.target.closest('.area-listing-current-location')) {
                        [200, 800, 1600, 3200].forEach(function (delay) {
                            setTimeout(function () {
                                if (!window.__areaListingGpsSilent) {
                                    syncAll();
                                }
                            }, delay);
                        });
                    }
                }, true);

                [100, 600, 1600, 3200, 6000].forEach(function (delay) {
                    setTimeout(syncAll, delay);
                });

            }

            var areaListingAdminBooted = false;
            const bootAreaListingAdmin = function () {
            if (areaListingAdminBooted) return;
            bootAreaListingNativeSync();
            if (!window.jQuery) return;
            areaListingAdminBooted = true;
            var $ = window.jQuery;

            function titleCase(value) {
                return String(value || '').toLowerCase().replace(/\b\w/g, function (letter) { return letter.toUpperCase(); }).trim();
            }

            function areaListingWrapper(context) {
                const item = $(context);
                const form = item.closest('form');
                if (form.length && form.find('.area-listing-admin-area').length) {
                    return form;
                }

                const row = item.closest('.row');
                if (row.length && row.find('.area-listing-admin-area').length) {
                    return row;
                }

                const area = $('.area-listing-admin-area').first();
                const areaForm = area.closest('form');
                return areaForm.length ? areaForm : area.closest('.row');
            }

            function findOptionValue(select, matcher) {
                let matchedValue = '';
                select.find('option').each(function () {
                    if (matchedValue || !$(this).val() || $(this).prop('disabled')) return;
                    if (matcher($(this))) matchedValue = $(this).val();
                });
                return matchedValue;
            }

            function destroyHiddenLocationSelect2(wrapper) {
                if (!$.fn.select2) return;
                wrapper.find('.area-listing-admin-state, .area-listing-admin-city').each(function () {
                    const select = $(this);
                    if (select.hasClass('select2-hidden-accessible')) {
                        try {
                            select.select2('destroy');
                        } catch (e) { /* ignore */ }
                    }
                    select.next('.select2-container').remove();
                });
            }

            function syncMainCityFields(wrapper) {
                const searchInput = wrapper.find('#searchInput, input[placeholder="City"]');
                const hiddenCityInput = wrapper.find('#city, input[name="city"]');
                if (!searchInput.length || !hiddenCityInput.length) return;

                const searchValue = String(searchInput.val() || '').trim();
                if (searchValue !== '') {
                    hiddenCityInput.val(searchValue);
                }
            }

            function refreshSelect2Value(select) {
                if (!select.length || !$.fn.select2 || !select.hasClass('select2-hidden-accessible')) {
                    return;
                }

                const selected = select.find('option:selected');
                const text = selected.length ? selected.text() : (select.find('option[value=""]').first().text() || '');
                const rendered = select.next('.select2-container').find('.select2-selection__rendered');
                if (rendered.length) {
                    rendered.text(text).attr('title', text);
                }
            }

            function setGpsLock(active) {
                $('body').data('areaListingGpsLock', !!active);
                window.__areaListingGpsSilent = !!active;
            }

            function isGpsLocked() {
                return !!$('body').data('areaListingGpsLock');
            }

            function silentSetSelectValue(select, value) {
                select.val(value || '');
                refreshSelect2Value(select);
            }

            function syncCoreLocationFields(wrapper) {
                if (wrapper.data('areaListingSyncing')) return;
                wrapper.data('areaListingSyncing', true);

                syncMainCityFields(wrapper);

                const stateName = titleCase(wrapper.find('#state, input[name="state"]').val() || '');
                const cityName = titleCase(wrapper.find('#searchInput, input[placeholder="City"]').val() || wrapper.find('#city, input[name="city"]').val() || '');
                const countryInput = wrapper.find('#country, input[name="country"]');
                const stateSelect = wrapper.find('.area-listing-admin-state');
                const citySelect = wrapper.find('.area-listing-admin-city');

                if (countryInput.length && !countryInput.val()) {
                    countryInput.val('India');
                }

                if (stateName) {
                    const stateValue = findOptionValue(stateSelect, function (option) {
                        return titleCase(option.data('name') || option.text()) === stateName;
                    });
                    if (stateValue) {
                        stateSelect.val(stateValue);
                    }
                }

                const stateId = String(stateSelect.val() || '');
                citySelect.find('option').each(function () {
                    const optionStateId = String($(this).data('state-id') || '');
                    $(this).prop('disabled', !!optionStateId && !!stateId && optionStateId !== stateId);
                });

                if (cityName) {
                    const cityValue = findOptionValue(citySelect, function (option) {
                        const optionStateId = String($(this).data('state-id') || '');
                        return titleCase(option.data('name') || option.text()) === cityName
                            && (!optionStateId || !stateId || optionStateId === stateId);
                    });
                    if (cityValue) {
                        citySelect.val(cityValue);
                    } else {
                        citySelect.val('');
                    }
                } else {
                    citySelect.val('');
                }

                wrapper.data('areaListingSyncing', false);
            }

            function normalizeKey(value) {
                return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
            }

            function optionMatchesCity($option, cityId, wantedCity) {
                if (!$option.val()) return true;
                const optionCityId = String($option.data('city-id') || '');
                const optionCity = normalizeKey($option.data('city') || String($option.text()).split(' - ')[0]);
                wantedCity = normalizeKey(wantedCity);
                if (cityId) {
                    if (optionCityId) return optionCityId === String(cityId);
                    return !!wantedCity && optionCity === wantedCity;
                }
                return !wantedCity || optionCity === wantedCity;
            }

            function optionMatchesArea($option, areaId) {
                if (!$option.val()) return true;
                const optionAreaId = String($option.data('area-id') || '');
                if (!areaId) return false;
                return optionAreaId === String(areaId);
            }

            function applyAreaSubAreaFilters(wrapper) {
                const cityId = String(wrapper.find('.area-listing-admin-city').val() || '');
                const areaId = String(wrapper.find('.area-listing-admin-area').val() || '');
                const wantedCity = wrapper.find('#searchInput, input[placeholder="City"]').val() || wrapper.find('#city, input[name="city"]').val() || '';
                const hasCity = !!cityId || !!normalizeKey(wantedCity);

                wrapper.find('.area-listing-admin-area option').each(function () {
                    const $option = $(this);
                    if (!$option.val()) return;
                    $option.prop('disabled', !hasCity || !optionMatchesCity($option, cityId, wantedCity));
                });

                if (wrapper.find('.area-listing-admin-area option:selected').prop('disabled')) {
                    wrapper.find('.area-listing-admin-area').val('');
                }

                const activeAreaId = String(wrapper.find('.area-listing-admin-area').val() || '');
                wrapper.find('.area-listing-admin-sub-area option').each(function () {
                    const $option = $(this);
                    if (!$option.val()) return;
                    $option.prop('disabled', !optionMatchesArea($option, activeAreaId));
                });

                if (wrapper.find('.area-listing-admin-sub-area option:selected').prop('disabled')) {
                    wrapper.find('.area-listing-admin-sub-area').val('');
                }

                if (!activeAreaId) {
                    wrapper.find('.area-listing-admin-sub-area').val('');
                }
            }

            function syncSelects(wrapper) {
                if (window.__areaListingPinMoving) {
                    return;
                }

                syncCoreLocationFields(wrapper);

                const stateId = String(wrapper.find('.area-listing-admin-state').val() || '');
                const cityId = String(wrapper.find('.area-listing-admin-city').val() || '');
                const areaId = String(wrapper.find('.area-listing-admin-area').val() || '');

                wrapper.find('.area-listing-admin-city option').each(function () {
                    const optionStateId = String($(this).data('state-id') || '');
                    $(this).prop('disabled', !!optionStateId && !!stateId && optionStateId !== stateId);
                });
                if (wrapper.find('.area-listing-admin-city option:selected').is(':disabled')) {
                    wrapper.find('.area-listing-admin-city').val('');
                }

                applyAreaSubAreaFilters(wrapper);

                wrapper.find('.area-listing-admin-city').prop('disabled', !stateId);
                wrapper.find('.area-listing-admin-area').prop('disabled', !cityId && !normalizeKey(wrapper.find('#searchInput, #city').val()));
                wrapper.find('.area-listing-admin-sub-area').prop('disabled', !String(wrapper.find('.area-listing-admin-area').val() || ''));

                if ($.fn.select2) {
                    wrapper.find('.area-listing-admin-area, .area-listing-admin-sub-area').each(function () {
                        refreshSelect2Value($(this));
                    });
                }
                refreshSelect2Placeholders(wrapper);
                refreshCityHint(wrapper);
            }

            function selectOptionByText(select, matcher) {
                let matchedValue = '';
                select.find('option').each(function () {
                    if (matchedValue || $(this).is(':disabled') || !$(this).val()) return;
                    if (matcher($(this))) matchedValue = $(this).val();
                });
                if (matchedValue) {
                    if (isGpsLocked()) {
                        silentSetSelectValue(select, matchedValue);
                    } else {
                        select.val(matchedValue).trigger('change');
                    }
                    return true;
                }
                return false;
            }

            function refreshAreaSubAreaSelect2(wrapper) {
                if (!$.fn.select2) {
                    return;
                }
                refreshSelect2Value(wrapper.find('.area-listing-admin-area'));
                refreshSelect2Value(wrapper.find('.area-listing-admin-sub-area'));
            }

            function forceClearAreaSubAreaSelection(wrapper) {
                const areaSelect = wrapper.find('.area-listing-admin-area');
                const subSelect = wrapper.find('.area-listing-admin-sub-area');

                areaSelect.find('option').prop('selected', false);
                subSelect.find('option').prop('selected', false);
                wrapper.find('.area-listing-detected-area, .area-listing-detected-sub-area').val('');

                if ($.fn.select2 && areaSelect.hasClass('select2-hidden-accessible')) {
                    areaSelect.val(null);
                    subSelect.val(null);
                    refreshSelect2Value(areaSelect);
                    refreshSelect2Value(subSelect);
                    areaSelect.trigger('change.select2');
                    subSelect.trigger('change.select2');
                } else {
                    areaSelect.val('');
                    subSelect.val('');
                }

                applyAreaSubAreaFilters(wrapper);
                refreshAreaSubAreaSelect2(wrapper);
            }

            function clearAreaSubAreaForGps(wrapper) {
                forceClearAreaSubAreaSelection(wrapper);
            }

            function beginGpsLocationApply() {
                setGpsLock(true);
            }

            function endGpsLocationApply() {
                setGpsLock(false);
            }

            function refreshCityHint(wrapper) {
                const hint = wrapper.find('.area-listing-city-hint');
                if (!hint.length) return;

                syncMainCityFields(wrapper);
                const cityName = titleCase(wrapper.find('#searchInput, input[placeholder="City"]').val() || wrapper.find('#city, input[name="city"]').val() || '');
                const stateName = titleCase(wrapper.find('#state, input[name="state"]').val() || '');
                const countryName = titleCase(wrapper.find('#country, input[name="country"]').val() || 'India');
                const parts = [cityName, stateName, countryName].filter(Boolean);

                hint.text(parts.length
                    ? '{{ __('Filtering areas for') }}: ' + parts.join(', ')
                    : '{{ __('Area and Sub Area use the City, State, and Country fields above.') }}');
            }

            function refreshSelect2Placeholders(wrapper) {
                [
                    ['.area-listing-admin-state', '{{ __('Select State') }}'],
                    ['.area-listing-admin-city', '{{ __('Select City') }}'],
                    ['.area-listing-admin-area', '{{ __('Select Area') }}'],
                    ['.area-listing-admin-sub-area', '{{ __('Select Sub Area') }}'],
                ].forEach(function (item) {
                    const select = wrapper.find(item[0]);
                    if (!select.length || String(select.val() || '') !== '') return;

                    const rendered = select.next('.select2-container').find('.select2-selection__rendered');
                    if (rendered.length) {
                        rendered.text(item[1]).attr('title', item[1]);
                    }
                });
                refreshCityHint(wrapper);
            }

            function initAreaListingSelect2(wrapper) {
                if (!$.fn.select2) return;

                destroyHiddenLocationSelect2(wrapper);

                wrapper.find('.area-listing-admin-area, .area-listing-admin-sub-area').each(function () {
                    const select = $(this);
                    const kind = select.hasClass('area-listing-admin-area') ? 'area' : 'sub-area';
                    const placeholderText = kind === 'area' ? '{{ __('Select Area') }}' : '{{ __('Select Sub Area') }}';
                    const emptyOptions = select.find('option[value=""]');
                    emptyOptions.not(':first').remove();
                    emptyOptions.first().text(placeholderText);

                    if (select.hasClass('select2-hidden-accessible')) {
                        select.select2('destroy');
                    }
                    select.next('.select2-container').remove();

                    select.select2({
                        width: '100%',
                        theme: 'bootstrap-5',
                        allowClear: true,
                        tags: true,
                        createTag: function (params) {
                            const term = $.trim(params.term || '');
                            if (!term) return null;
                            return {
                                id: '__new__:' + term,
                                text: term,
                                isNew: true
                            };
                        },
                        insertTag: function (data, tag) {
                            data.unshift(tag);
                        },
                        templateResult: function (data) {
                            if (data.element && data.element.disabled) {
                                return null;
                            }
                            if (data.isNew || (data.id && String(data.id).indexOf('__new__:') === 0)) {
                                return $('<span>' + '{{ __('Add') }}' + ' "' + data.text + '"</span>');
                            }
                            return data.text;
                        },
                        matcher: function (params, data) {
                            if (data.element && data.element.disabled) {
                                return null;
                            }
                            const term = $.trim(params.term || '').toLowerCase();
                            if (!term) {
                                return data;
                            }
                            return String(data.text || '').toLowerCase().indexOf(term) >= 0 ? data : null;
                        },
                        placeholder: placeholderText
                    });
                    select.next('.select2-container').addClass('area-listing-location-select2');
                });
                refreshSelect2Placeholders(wrapper);
            }

            function csrfToken() {
                const tokenInput = $('input[name="_token"]').first().val();
                const tokenMeta = $('meta[name="csrf-token"]').attr('content');
                return tokenInput || tokenMeta || '';
            }

            function areaListingNotify(message, type, options) {
                options = options || {};
                if (typeof Toastify === 'function') {
                    const colors = {
                        success: 'linear-gradient(to right, #00b09b, #96c93d)',
                        danger: '#dc3545',
                        warning: '#f59e0b',
                        info: '#0ea5e9',
                    };
                    const toastOptions = {
                        text: message,
                        duration: options.duration || 8000,
                        close: true,
                        backgroundColor: colors[type] || colors.warning,
                    };
                    if (options.multiline) {
                        toastOptions.escapeMarkup = false;
                        toastOptions.style = { whiteSpace: 'pre-line', maxWidth: '420px', lineHeight: '1.45' };
                    }
                    Toastify(toastOptions).showToast();
                    return;
                }
                alert(message);
            }

            function removeAreaSimilarPrompt(wrapper) {
                wrapper.find('.area-listing-similar-prompt').remove();
            }

            function appendAreaOption(wrapper, area, fallbackName, cityId, citySelect, stateSelect) {
                const areaSelect = wrapper.find('.area-listing-admin-area');
                const name = area.name || fallbackName;
                const cityLabel = area.city || citySelect.find('option:selected').data('name') || '';
                let option = areaSelect.find('option[value="' + area.id + '"]');
                if (!option.length) {
                    option = $('<option></option>')
                        .val(String(area.id))
                        .attr('data-city-id', area.city_id || cityId)
                        .attr('data-city', cityLabel)
                        .attr('data-state', area.state || (stateSelect.find('option:selected').data('name') || ''))
                        .text(cityLabel + ' - ' + name);
                    areaSelect.append(option);
                }
                areaSelect.val(String(area.id)).trigger('change');
                wrapper.find('.area-listing-detected-area').val(name);
                syncSelects(wrapper);
            }

            function showAreaSimilarPrompt(wrapper, areaSelect, payload, requestData) {
                removeAreaSimilarPrompt(wrapper);
                const suggestion = payload && payload.suggestion ? payload.suggestion : {};
                const prompt = $('<div class="area-listing-similar-prompt alert alert-warning alert-dismissible fade show mt-2" role="alert"></div>');
                const body = $('<div class="d-flex flex-wrap align-items-center gap-2 pe-4"></div>');
                body.append($('<span></span>').text('{{ __('Similar area') }} "' + (suggestion.name || '') + '" {{ __('already exists.') }} '));
                const actions = $('<span class="d-inline-flex flex-wrap gap-2"></span>');
                const useExistingBtn = $('<button type="button" class="btn btn-sm btn-success"></button>').text('{{ __('Use Existing') }}');
                const createAnywayBtn = $('<button type="button" class="btn btn-sm btn-outline-primary"></button>').text('{{ __('Create New Anyway') }}');
                actions.append(useExistingBtn, createAnywayBtn);
                body.append(actions);
                prompt.append(body);
                prompt.append($('<button type="button" class="btn-close" aria-label="Close"></button>').on('click', function () {
                    removeAreaSimilarPrompt(wrapper);
                    areaSelect.val('').trigger('change');
                }));
                areaSelect.closest('.form-group').append(prompt);

                useExistingBtn.on('click', function () {
                    removeAreaSimilarPrompt(wrapper);
                    appendAreaOption(wrapper, {
                        id: suggestion.id,
                        name: suggestion.name,
                        city_id: requestData.city_id,
                        city: requestData.city,
                        state: requestData.state,
                    }, requestData.name, requestData.city_id, wrapper.find('.area-listing-admin-city'), wrapper.find('.area-listing-admin-state'));
                });

                createAnywayBtn.on('click', function () {
                    removeAreaSimilarPrompt(wrapper);
                    requestData.force_create = true;
                    createAdminArea(wrapper, requestData.name, requestData);
                });
            }

            function normalizeLocationLabel(value) {
                return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
            }

            function resolveAdminCityContext(wrapper) {
                const citySelect = wrapper.find('.area-listing-admin-city');
                const stateSelect = wrapper.find('.area-listing-admin-state');
                const manualCity = $.trim(
                    wrapper.find('#city, input[name="city"], #searchInput, input[placeholder="City"]').first().val() || ''
                );
                let cityId = String(citySelect.val() || '');
                let cityName = citySelect.find('option:selected').data('name') || manualCity || '';

                if (!cityId && manualCity) {
                    const wanted = normalizeLocationLabel(manualCity);
                    citySelect.find('option').each(function () {
                        const option = $(this);
                        const optionValue = String(option.val() || '');
                        if (!optionValue) {
                            return;
                        }
                        const optionName = normalizeLocationLabel(option.data('name') || option.text());
                        if (optionName === wanted) {
                            cityId = optionValue;
                            cityName = option.data('name') || manualCity;
                            return false;
                        }
                    });
                }

                if (!cityName && cityId) {
                    cityName = citySelect.find('option:selected').data('name') || citySelect.find('option:selected').text();
                }

                return {
                    cityId: cityId,
                    cityName: $.trim(cityName),
                    stateId: String(stateSelect.val() || ''),
                    stateName: stateSelect.find('option:selected').data('name')
                        || $.trim(wrapper.find('#state, input[name="state"]').first().val() || ''),
                    country: stateSelect.find('option:selected').data('country')
                        || $.trim(wrapper.find('#country, input[name="country"]').first().val() || '')
                        || 'India',
                };
            }

            function ensureCitySelectOption(wrapper, context) {
                if (!context || !context.cityId || !context.cityName) {
                    return;
                }
                const citySelect = wrapper.find('.area-listing-admin-city');
                let option = citySelect.find('option[value="' + context.cityId + '"]');
                if (!option.length) {
                    option = $('<option></option>')
                        .val(context.cityId)
                        .attr('data-state-id', context.stateId || '')
                        .attr('data-name', context.cityName)
                        .text(context.cityName);
                    citySelect.append(option);
                }
                citySelect.val(String(context.cityId));
            }

            function buildAreaCreatePayload(wrapper, rawName, options) {
                options = options || {};
                const ctx = resolveAdminCityContext(wrapper);
                const payload = {
                    _token: csrfToken(),
                    name: titleCase(rawName),
                    state_id: ctx.stateId,
                    city_id: ctx.cityId,
                    city: ctx.cityName,
                    state: ctx.stateName,
                    country: ctx.country,
                    center_lat: wrapper.find('#latitude, input[name="latitude"]').val() || '',
                    center_lng: wrapper.find('#longitude, input[name="longitude"]').val() || '',
                };
                if (options.force_create) {
                    payload.force_create = true;
                }
                return payload;
            }

            function createAdminArea(wrapper, rawName, options) {
                const citySelect = wrapper.find('.area-listing-admin-city');
                const stateSelect = wrapper.find('.area-listing-admin-state');
                const areaSelect = wrapper.find('.area-listing-admin-area');
                const cityContext = resolveAdminCityContext(wrapper);
                if (!cityContext.cityName) {
                    areaListingNotify('{{ __('Please select city first to add area.') }}', 'warning');
                    areaSelect.val('').trigger('change');
                    return;
                }

                const requestData = buildAreaCreatePayload(wrapper, rawName, options);
                $.ajax({
                    url: '{{ url('area-listing/areas') }}',
                    method: 'POST',
                    dataType: 'json',
                    data: requestData
                }).done(function (response) {
                    const area = response && response.data ? response.data : null;
                    if (!area || !area.id) return;
                    removeAreaSimilarPrompt(wrapper);
                    ensureCitySelectOption(wrapper, {
                        cityId: area.city_id || cityContext.cityId,
                        cityName: area.city || cityContext.cityName,
                        stateId: area.state_id || cityContext.stateId,
                    });
                    appendAreaOption(wrapper, area, requestData.name, area.city_id || cityContext.cityId, citySelect, stateSelect);
                }).fail(function (xhr) {
                    const payload = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                    if (xhr.status === 409 && payload && payload.status === 'similar_exists') {
                        areaSelect.val('').trigger('change');
                        showAreaSimilarPrompt(wrapper, areaSelect, payload, requestData);
                        return;
                    }
                    const message = payload && payload.message ? payload.message : '{{ __('Unable to add area.') }}';
                    areaListingNotify(message, 'danger');
                    areaSelect.val('').trigger('change');
                });
            }

            function removeSubAreaSimilarPrompt(wrapper) {
                wrapper.find('.area-listing-sub-similar-prompt').remove();
            }

            function appendSubAreaOption(wrapper, subArea, fallbackName, areaId) {
                const subSelect = wrapper.find('.area-listing-admin-sub-area');
                const areaSelect = wrapper.find('.area-listing-admin-area');
                const name = subArea.name || fallbackName;
                const areaName = areaSelect.find('option:selected').text().split(' - ').pop().trim();
                let option = subSelect.find('option[value="' + subArea.id + '"]');
                if (!option.length) {
                    option = $('<option></option>')
                        .val(String(subArea.id))
                        .attr('data-area-id', subArea.area_id || areaId)
                        .text((areaName || '{{ __('Area') }}') + ' - ' + name);
                    subSelect.append(option);
                }
                subSelect.val(String(subArea.id)).trigger('change');
                wrapper.find('.area-listing-detected-sub-area').val(name);
                syncSelects(wrapper);
            }

            function showSubAreaSimilarPrompt(wrapper, subSelect, payload, requestData) {
                removeSubAreaSimilarPrompt(wrapper);
                const suggestion = payload && payload.suggestion ? payload.suggestion : {};
                const prompt = $('<div class="area-listing-sub-similar-prompt alert alert-warning alert-dismissible fade show mt-2" role="alert"></div>');
                const body = $('<div class="d-flex flex-wrap align-items-center gap-2 pe-4"></div>');
                body.append($('<span></span>').text('{{ __('Similar sub area') }} "' + (suggestion.name || '') + '" {{ __('already exists.') }} '));
                const actions = $('<span class="d-inline-flex flex-wrap gap-2"></span>');
                const useExistingBtn = $('<button type="button" class="btn btn-sm btn-success"></button>').text('{{ __('Use Existing') }}');
                const createAnywayBtn = $('<button type="button" class="btn btn-sm btn-outline-primary"></button>').text('{{ __('Create New Anyway') }}');
                actions.append(useExistingBtn, createAnywayBtn);
                body.append(actions);
                prompt.append(body);
                prompt.append($('<button type="button" class="btn-close" aria-label="Close"></button>').on('click', function () {
                    removeSubAreaSimilarPrompt(wrapper);
                    subSelect.val('').trigger('change');
                }));
                subSelect.closest('.form-group').append(prompt);

                useExistingBtn.on('click', function () {
                    removeSubAreaSimilarPrompt(wrapper);
                    appendSubAreaOption(wrapper, {
                        id: suggestion.id,
                        name: suggestion.name,
                        area_id: suggestion.area_id || requestData.area_id,
                    }, requestData.name, requestData.area_id);
                });

                createAnywayBtn.on('click', function () {
                    removeSubAreaSimilarPrompt(wrapper);
                    requestData.force_create = true;
                    createAdminSubArea(wrapper, requestData.name, requestData);
                });
            }

            function buildSubAreaCreatePayload(wrapper, rawName, options) {
                options = options || {};
                const areaSelect = wrapper.find('.area-listing-admin-area');
                const payload = {
                    _token: csrfToken(),
                    area_id: String(areaSelect.val() || ''),
                    name: titleCase(rawName),
                    center_lat: wrapper.find('#latitude, input[name="latitude"]').val() || '',
                    center_lng: wrapper.find('#longitude, input[name="longitude"]').val() || '',
                };
                if (options.force_create) {
                    payload.force_create = true;
                }
                return payload;
            }

            function createAdminSubArea(wrapper, rawName, options) {
                const areaSelect = wrapper.find('.area-listing-admin-area');
                const subSelect = wrapper.find('.area-listing-admin-sub-area');
                const areaId = String(areaSelect.val() || '');
                if (!areaId || areaId.indexOf('__new__:') === 0) {
                    areaListingNotify('{{ __('Please select area first to add sub area.') }}', 'warning');
                    subSelect.val('').trigger('change');
                    return;
                }

                const requestData = typeof options === 'object' && options !== null && options.area_id
                    ? options
                    : buildSubAreaCreatePayload(wrapper, rawName, options);
                $.ajax({
                    url: '{{ url('area-listing/sub-areas') }}',
                    method: 'POST',
                    dataType: 'json',
                    data: requestData
                }).done(function (response) {
                    const subArea = response && response.data ? response.data : null;
                    if (!subArea || !subArea.id) return;
                    removeSubAreaSimilarPrompt(wrapper);
                    appendSubAreaOption(wrapper, subArea, requestData.name, areaId);
                }).fail(function (xhr) {
                    const payload = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                    if (xhr.status === 409 && payload && payload.status === 'similar_exists') {
                        subSelect.val('').trigger('change');
                        showSubAreaSimilarPrompt(wrapper, subSelect, payload, requestData);
                        return;
                    }
                    const message = payload && payload.message ? payload.message : '{{ __('Unable to add sub area.') }}';
                    areaListingNotify(message, 'danger');
                    subSelect.val('').trigger('change');
                });
            }

            $(document).on('change', '.area-listing-admin-area', function () {
                if (window.__areaListingPinMoving) {
                    return;
                }
                const wrapper = areaListingWrapper(this);
                const areaSelect = wrapper.find('.area-listing-admin-area');
                const areaVal = String(areaSelect.val() || '');
                const previousAreaVal = areaSelect.data('areaListingPrevVal');
                const selectedArea = areaSelect.find('option:selected').text().split(' - ').pop().trim();
                wrapper.find('.area-listing-detected-area').val(areaVal ? selectedArea : '');
                areaSelect.data('areaListingPrevVal', areaVal);
                if (areaVal) {
                    clearDetectedAreaFields(wrapper);
                } else {
                    refreshDetectedAreaInfo(wrapper);
                }
                if (!wrapper.data('areaListingSyncing') && previousAreaVal !== undefined && previousAreaVal !== areaVal) {
                    wrapper.find('.area-listing-admin-sub-area').val('');
                    wrapper.find('.area-listing-detected-sub-area').val('');
                    if ($.fn.select2) {
                        wrapper.find('.area-listing-admin-sub-area').trigger('change.select2');
                    }
                    syncSelects(wrapper);
                }
            });

            $(document).on('input change blur', '#state, input[name="state"], #city, input[name="city"], #searchInput, input[placeholder="City"], #country, input[name="country"]', function () {
                if (window.__areaListingPinMoving) {
                    return;
                }
                const wrapper = areaListingWrapper(this);
                syncSelects(wrapper);
            });

            $(document).on('change', '.area-listing-admin-city', function () {
                if (window.__areaListingPinMoving) {
                    return;
                }
                const wrapper = areaListingWrapper(this);
                wrapper.find('.area-listing-admin-area, .area-listing-admin-sub-area').val('');
                wrapper.find('.area-listing-detected-area, .area-listing-detected-sub-area').val('');
                syncSelects(wrapper);
            });


            $(document).on('change', '.area-listing-admin-area', function () {
                const select = $(this);
                const value = String(select.val() || '');
                if (value.indexOf('__new__:') !== 0) return;
                const wrapper = areaListingWrapper(select);
                const rawName = value.replace('__new__:', '');
                createAdminArea(wrapper, rawName);
            });

            $(document).on('change', '.area-listing-admin-sub-area', function () {
                const wrapper = areaListingWrapper(this);
                if (String($(this).val() || '').trim()) {
                    wrapper.find('#detected_sub_area_name_input, .area-listing-detected-sub-area').val('');
                    window.__detectedSubAreaName = '';
                    refreshDetectedAreaInfo(wrapper);
                }
                const select = $(this);
                const value = String(select.val() || '');
                if (value.indexOf('__new__:') === 0) {
                    const wrapper = areaListingWrapper(select);
                    const rawName = value.replace('__new__:', '');
                    createAdminSubArea(wrapper, rawName);
                    return;
                }
                const selectedText = select.find('option:selected').text().split(' - ').pop().trim();
                areaListingWrapper(select).find('.area-listing-detected-sub-area').val(select.val() ? selectedText : '');
            });

            $(document).on('select2:select', '.area-listing-admin-area', function (event) {
                const data = event && event.params ? event.params.data : null;
                const value = String((data && data.id) || $(this).val() || '');
                if (value.indexOf('__new__:') !== 0) return;
                const wrapper = areaListingWrapper(this);
                createAdminArea(wrapper, value.replace('__new__:', ''));
            });

            $(document).on('select2:select', '.area-listing-admin-sub-area', function (event) {
                const data = event && event.params ? event.params.data : null;
                const value = String((data && data.id) || $(this).val() || '');
                if (value.indexOf('__new__:') !== 0) return;
                const wrapper = areaListingWrapper(this);
                createAdminSubArea(wrapper, value.replace('__new__:', ''));
            });

            $(document).on('select2:opening', '.area-listing-admin-area, .area-listing-admin-sub-area', function (event) {
                if (isGpsLocked()) {
                    event.preventDefault();
                    return;
                }

                const select = $(this);
                if (select.next('.select2-container').hasClass('area-listing-location-select2')) return;

                event.preventDefault();
                const wrapper = areaListingWrapper(select);
                initAreaListingSelect2(wrapper);
                syncSelects(wrapper);

                setTimeout(function () {
                    if (!select.prop('disabled')) {
                        select.select2('open');
                    }
                }, 0);
            });

            $(document).on('keydown', '.select2-container--open .select2-search__field', function (event) {
                if (event.key !== 'Enter') return;
                const term = titleCase($(this).val() || '');
                if (!term) return;

                const open = $('.select2-container--open');
                const select = open.prev('select');
                if (!select.length) return;

                const wrapper = areaListingWrapper(select);
                if (select.hasClass('area-listing-admin-area')) {
                    event.preventDefault();
                    createAdminArea(wrapper, term);
                    select.select2('close');
                } else if (select.hasClass('area-listing-admin-sub-area')) {
                    event.preventDefault();
                    createAdminSubArea(wrapper, term);
                    select.select2('close');
                }
            });

            


            $(document).on('blur', '.area-listing-detected-area, .area-listing-detected-sub-area', function () {
                $(this).val(titleCase($(this).val()));
            });

            function requestAreaListingPosition() {
                return new Promise(function (resolve, reject) {
                    if (!navigator.geolocation) {
                        reject(new Error('{{ __('Location is not supported by this browser.') }}'));
                        return;
                    }
                    let bestPosition = null;
                    const pickBetter = function (next) {
                        if (!next || !next.coords) return;
                        if (!bestPosition || Number(next.coords.accuracy || 999999) < Number(bestPosition.coords.accuracy || 999999)) {
                            bestPosition = next;
                        }
                    };
                    navigator.geolocation.getCurrentPosition(function (position) {
                        pickBetter(position);
                        if (!position.coords.accuracy || position.coords.accuracy <= 5000) {
                            resolve(position);
                            return;
                        }
                        let settled = false;
                        let watchId = null;
                        const finish = function () {
                            if (settled) return;
                            settled = true;
                            if (watchId !== null) navigator.geolocation.clearWatch(watchId);
                            if (bestPosition && bestPosition.coords) {
                                resolve(bestPosition);
                            } else {
                                reject(new Error('{{ __('Unable to get current location.') }}'));
                            }
                        };
                        const timer = setTimeout(finish, 12000);
                        watchId = navigator.geolocation.watchPosition(function (watched) {
                            pickBetter(watched);
                            if (bestPosition && bestPosition.coords && (!bestPosition.coords.accuracy || bestPosition.coords.accuracy <= 5000)) {
                                clearTimeout(timer);
                                finish();
                            }
                        }, function () {}, { enableHighAccuracy: true, timeout: 25000, maximumAge: 0 });
                    }, reject, { enableHighAccuracy: true, timeout: 25000, maximumAge: 0 });
                });
            }

            function parseAreaFromFormattedAddress(result, cityName) {
                const formatted = String(result && result.formatted_address ? result.formatted_address : '');
                if (!formatted) {
                    return '';
                }

                const parts = formatted.split(',').map(function (part) {
                    return part.trim();
                }).filter(Boolean);
                const cityNorm = normalizeKey(cityName);
                let cityIndex = -1;

                for (let i = 0; i < parts.length; i++) {
                    const partNorm = normalizeKey(parts[i]);
                    if (!cityNorm) {
                        continue;
                    }
                    if (partNorm === cityNorm || partNorm.indexOf(cityNorm) >= 0 || cityNorm.indexOf(partNorm) >= 0) {
                        cityIndex = i;
                        break;
                    }
                }

                if (cityIndex > 0) {
                    const candidate = parts[cityIndex - 1];
                    if (candidate && !/^\d+$/.test(candidate) && !/^\d{5,6}$/.test(candidate.replace(/\s/g, ''))) {
                        return titleCase(candidate);
                    }
                }

                return '';
            }

            function extractPlaceComponents(result) {
                const find = function (types) {
                    const components = result.address_components || [];
                    const component = components.find(function (item) {
                        return types.some(function (type) { return item.types.indexOf(type) !== -1; });
                    });
                    return component ? component.long_name : '';
                };

                const detectedCity = find(['locality', 'administrative_area_level_3', 'administrative_area_level_2']);
                let detectedArea = find(['sublocality_level_1', 'sublocality_level_2', 'neighborhood', 'sublocality']);
                if (!detectedArea) {
                    detectedArea = parseAreaFromFormattedAddress(result, detectedCity);
                }

                return {
                    detectedState: find(['administrative_area_level_1']),
                    detectedCity: detectedCity,
                    detectedCountry: find(['country']) || 'India',
                    detectedArea: titleCase(detectedArea),
                    detectedSubArea: titleCase(find(['sublocality_level_2', 'sublocality_level_3'])),
                };
            }

            function findAreaOptionValueByName(areaSelect, wantedName) {
                const wanted = normalizeKey(wantedName);
                if (!wanted) {
                    return '';
                }

                let matchedValue = '';
                let partialValue = '';
                areaSelect.find('option').each(function () {
                    if ($(this).is(':disabled') || !$(this).val()) {
                        return;
                    }
                    const label = normalizeKey(String($(this).text()).split(' - ').pop());
                    if (label === wanted) {
                        matchedValue = $(this).val();
                        return false;
                    }
                    if (!partialValue && (label.indexOf(wanted) >= 0 || wanted.indexOf(label) >= 0)) {
                        partialValue = $(this).val();
                    }
                });
                return matchedValue || partialValue;
            }

            function buildLocationComponentsPayload(result) {
                return (result.address_components || []).map(function (item) {
                    return {
                        name: item.long_name || item.short_name || '',
                        types: item.types || [],
                    };
                });
            }

            function applyResolvedLocationFromServer(wrapper, data, deferSync) {
                if (!data || !data.area_id) {
                    return false;
                }

                const areaSelect = wrapper.find('.area-listing-admin-area');
                const subSelect = wrapper.find('.area-listing-admin-sub-area');
                const citySelect = wrapper.find('.area-listing-admin-city');
                const stateSelect = wrapper.find('.area-listing-admin-state');
                const ctx = resolveAdminCityContext(wrapper);

                if (data.city_id) {
                    ensureCitySelectOption(wrapper, {
                        cityId: data.city_id,
                        cityName: ctx.cityName,
                        stateId: ctx.stateId,
                        stateName: ctx.stateName,
                    });
                }

                syncCoreLocationFields(wrapper);
                wrapper.find('.area-listing-detected-area').val(data.detected_area_name || data.area_name || '');
                wrapper.find('.area-listing-detected-sub-area').val(data.detected_sub_area_name || data.sub_area_name || '');

                if (!areaSelect.find('option[value="' + data.area_id + '"]').length) {
                    appendAreaOption(wrapper, {
                        id: data.area_id,
                        name: data.area_name,
                        city_id: data.city_id,
                        city: ctx.cityName,
                        state: ctx.stateName,
                    }, data.area_name, data.city_id, citySelect, stateSelect);
                } else if (isGpsLocked() || window.__areaListingPinMoving) {
                    silentSetSelectValue(areaSelect, String(data.area_id));
                } else {
                    areaSelect.val(String(data.area_id)).trigger('change');
                }
                areaSelect.data('areaListingPrevVal', String(data.area_id));

                applyAreaSubAreaFilters(wrapper);

                if (data.sub_area_id) {
                    if (!subSelect.find('option[value="' + data.sub_area_id + '"]').length) {
                        appendSubAreaOption(wrapper, {
                            id: data.sub_area_id,
                            name: data.sub_area_name,
                        }, data.sub_area_name, data.area_id);
                    } else if (isGpsLocked() || window.__areaListingPinMoving) {
                        silentSetSelectValue(subSelect, String(data.sub_area_id));
                    } else {
                        subSelect.val(String(data.sub_area_id)).trigger('change');
                    }
                } else {
                    subSelect.val('');
                }

                refreshAreaSubAreaSelect2(wrapper);
                if (!deferSync) {
                    syncSelects(wrapper);
                }
                return true;
            }

            function applySubAreaClientMatch(wrapper, parts, deferSync) {
                const subAreaSelect = wrapper.find('.area-listing-admin-sub-area');
                const subValue = findAreaOptionValueByName(subAreaSelect, parts.detectedSubArea);

                if (subValue) {
                    if (isGpsLocked() || window.__areaListingPinMoving) {
                        silentSetSelectValue(subAreaSelect, subValue);
                    } else {
                        subAreaSelect.val(subValue).trigger('change');
                    }
                } else if (parts.detectedSubArea) {
                    wrapper.find('.area-listing-detected-sub-area').val(parts.detectedSubArea);
                }

                refreshAreaSubAreaSelect2(wrapper);
                if (!deferSync) {
                    syncSelects(wrapper);
                }
            }

            function applyAreaSubAreaClientMatch(wrapper, parts, deferSync) {
                const areaSelect = wrapper.find('.area-listing-admin-area');
                const areaValue = findAreaOptionValueByName(areaSelect, parts.detectedArea);

                if (areaValue) {
                    if (isGpsLocked()) {
                        silentSetSelectValue(areaSelect, areaValue);
                    } else {
                        areaSelect.val(areaValue).trigger('change');
                    }
                    areaSelect.data('areaListingPrevVal', areaValue);
                } else {
                    if ($.fn.select2 && areaSelect.hasClass('select2-hidden-accessible')) {
                        areaSelect.val(null).trigger('change.select2');
                    } else {
                        areaSelect.val('');
                    }
                    wrapper.find('.area-listing-detected-area').val(parts.detectedArea || '');
                }

                applyAreaSubAreaFilters(wrapper);
                applySubAreaClientMatch(wrapper, parts, deferSync);
            }

            function finishPinMoveLocationSync(wrapper) {
                window.__areaListingPinMoving = false;
                wrapper.find('#state, input[name="state"], #city, input[name="city"], #searchInput, input[placeholder="City"], #country, input[name="country"], #address, textarea[name="address"]').trigger('change');
                syncSelects(wrapper);
                refreshDetectedAreaInfo(wrapper);
            }

            function clearDetectedAreaFields(wrapper, options) {
                options = options || {};
                window.__detectedAreaName = '';
                wrapper.find('#detected_area_name_input, .area-listing-detected-area').val('');
                if (!options.keepSub) {
                    window.__detectedSubAreaName = '';
                    wrapper.find('#detected_sub_area_name_input, .area-listing-detected-sub-area').val('');
                }
                if (typeof window.setDetectedAreaFormFields === 'function') {
                    window.setDetectedAreaFormFields('', options.keepSub ? (window.__detectedSubAreaName || wrapper.find('#detected_sub_area_name_input').val() || '') : '');
                }
                refreshDetectedAreaInfo(wrapper);
            }

            function refreshDetectedAreaInfo(wrapper) {
                const areaSelect = wrapper.find('.area-listing-admin-area');
                const subSelect = wrapper.find('.area-listing-admin-sub-area');
                const info = wrapper.find('.area-listing-detected-info');
                const subOnly = wrapper.find('.area-listing-detected-sub-only-info');
                const areaName = String(wrapper.find('#detected_area_name_input, .area-listing-detected-area').first().val() || window.__detectedAreaName || '').trim();
                const subName = String(wrapper.find('#detected_sub_area_name_input, .area-listing-detected-sub-area').first().val() || window.__detectedSubAreaName || '').trim();
                const hasArea = !!String(areaSelect.val() || '').trim();
                const hasSub = !!String(subSelect.val() || '').trim();

                subOnly.addClass('d-none');
                info.addClass('d-none');

                // Area already chosen in master list; pending sub area only
                if (hasArea && !hasSub && subName) {
                    subOnly.removeClass('d-none');
                    subOnly.find('.area-listing-detected-sub-area-label').text(subName);
                    return;
                }

                // Pending area from listing (typical user portal submit)
                if (!hasArea && areaName) {
                    info.removeClass('d-none');
                    info.find('.area-listing-detected-area-label').text(areaName);
                    const subRow = info.find('.area-listing-detected-sub-row');
                    if (subName) {
                        subRow.removeClass('d-none');
                        info.find('.area-listing-detected-sub-area-label').text(subName);
                        subOnly.removeClass('d-none');
                        subOnly.find('.area-listing-detected-sub-area-label').text(subName);
                        subOnly.find('.area-listing-detected-sub-create-note').text(
                            '{{ __('This sub area will be created with the area above when you save.') }}'
                        );
                    } else {
                        subRow.addClass('d-none');
                    }
                    return;
                }

                // Sub area only (no pending area name)
                if (!hasSub && subName) {
                    subOnly.removeClass('d-none');
                    subOnly.find('.area-listing-detected-sub-area-label').text(subName);
                }
            }

            function applyDetectedFromResolveResult(wrapper, geocodeResult, resolveData) {
                if (typeof window.applyDetectedAreaFromResolve === 'function') {
                    window.applyDetectedAreaFromResolve(geocodeResult, resolveData || {});
                } else if (resolveData && resolveData.area_id) {
                    clearDetectedAreaFields(wrapper);
                }
                refreshDetectedAreaInfo(wrapper);
            }

            function promptEditDetectedName(currentValue, label) {
                const next = window.prompt(label, currentValue || '');
                if (next === null) {
                    return currentValue;
                }
                return titleCase(String(next).trim());
            }

            window.onPinMoveComplete = function (geocodeResult, lat, lng, options) {
                options = options || {};
                if (!window.jQuery) {
                    return Promise.resolve();
                }

                const areaField = $('.area-listing-admin-area').first();
                if (!areaField.length) {
                    return Promise.resolve();
                }

                const wrapper = areaListingWrapper(areaField);

                wrapper.find('#latitude, input[name="latitude"]').val(lat);
                wrapper.find('#longitude, input[name="longitude"]').val(lng);

                if (!geocodeResult) {
                    finishPinMoveLocationSync(wrapper);
                    return Promise.resolve();
                }

                const parts = extractPlaceComponents(geocodeResult);

                wrapper.find('#address, textarea[name="address"]').val(geocodeResult.formatted_address || '');
                wrapper.find('#state, input[name="state"]').val(parts.detectedState);
                wrapper.find('#city, input[name="city"]').val(parts.detectedCity);
                wrapper.find('#searchInput, input[placeholder="City"]').val(parts.detectedCity);
                wrapper.find('#country, input[name="country"]').val(parts.detectedCountry);
                $('.pac-container').hide();
                syncCoreLocationFields(wrapper);

                forceClearAreaSubAreaSelection(wrapper);

                if (options.skipAreaAutoFill) {
                    applyDetectedFromResolveResult(wrapper, geocodeResult, null);
                    finishPinMoveLocationSync(wrapper);
                    return Promise.resolve();
                }

                const ctx = resolveAdminCityContext(wrapper);

                return $.ajax({
                    url: '{{ url('area-listing/resolve-coordinates') }}',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        _token: csrfToken(),
                        latitude: lat,
                        longitude: lng,
                        city_id: ctx.cityId,
                        city: parts.detectedCity || ctx.cityName,
                        state: parts.detectedState || ctx.stateName,
                        country: parts.detectedCountry || ctx.country || 'India',
                        location_components: JSON.stringify(buildLocationComponentsPayload(geocodeResult)),
                    },
                }).done(function (resp) {
                    const resolveData = resp && resp.data ? resp.data : null;
                    if (resolveData && resolveData.area_id && applyResolvedLocationFromServer(wrapper, resolveData, true)) {
                        clearDetectedAreaFields(wrapper, { keepSub: true });
                        if (!resolveData.sub_area_id && parts.detectedSubArea) {
                            wrapper.find('#detected_sub_area_name_input, .area-listing-detected-sub-area').val(parts.detectedSubArea);
                            window.__detectedSubAreaName = parts.detectedSubArea;
                            applySubAreaClientMatch(wrapper, parts, true);
                        }
                        refreshDetectedAreaInfo(wrapper);
                        return;
                    }
                    applyDetectedFromResolveResult(wrapper, geocodeResult, resolveData);
                }).fail(function () {
                    applyDetectedFromResolveResult(wrapper, geocodeResult, null);
                }).always(function () {
                    finishPinMoveLocationSync(wrapper);
                });
            };

            function reverseGeocodeForAreaListing(wrapper, lat, lng, gpsOptions) {
                const mapSelectors = {
                    inputSelector: '#searchInput',
                    citySelector: '#city',
                    countrySelector: '#country',
                    stateSelector: '#state',
                    addressSelector: '#address',
                    latitudeSelector: '#latitude',
                    longitudeSelector: '#longitude'
                };

                gpsOptions = gpsOptions || {};

                const applyResult = function (result, resolvedCoords) {
                    const pinLat = resolvedCoords && resolvedCoords.lat != null ? resolvedCoords.lat : lat;
                    const pinLng = resolvedCoords && resolvedCoords.lng != null ? resolvedCoords.lng : lng;
                    const movePin = function () {
                        if (window.moveBackendPlacesMapPin) {
                            return window.moveBackendPlacesMapPin(pinLat, pinLng, mapSelectors);
                        }
                        return Promise.resolve();
                    };

                    if (typeof window.onPinMoveComplete === 'function') {
                        const opts = Object.assign({ source: 'gps' }, gpsOptions);
                        const chain = window.onPinMoveComplete(result, pinLat, pinLng, opts);
                        if (chain && typeof chain.then === 'function') {
                            return chain.then(movePin);
                        }
                        return movePin();
                    }

                    return movePin();
                };

                if (window.applyBackendPlaceFromCoords) {
                    return window.applyBackendPlaceFromCoords(lat, lng, mapSelectors, function (result, resolvedCoords) {
                        return applyResult(result, resolvedCoords);
                    });
                }

                return $.get('/api/get-map-place-details', { latitude: lat, longitude: lng }).then(function (resp) {
                    const d = resp && resp.data ? resp.data : {};
                    const results = d.result ? [d.result] : (d.results || []);
                    const result = results.length ? results[0] : null;
                    let resolved = { lat: lat, lng: lng };
                    if (result && result.geometry && result.geometry.location) {
                        const geo = result.geometry.location;
                        const geoLat = typeof geo.lat === 'function' ? geo.lat() : geo.lat;
                        const geoLng = typeof geo.lng === 'function' ? geo.lng() : geo.lng;
                        if (Number.isFinite(Number(geoLat)) && Number.isFinite(Number(geoLng))) {
                            resolved = { lat: Number(geoLat), lng: Number(geoLng) };
                        }
                    }
                    applyResult(result, resolved);
                    return result;
                });
            }

            $(document).on('click', '.area-listing-current-location', function (event) {
                event.preventDefault();
                const wrapper = areaListingWrapper(this);
                const button = $(this);
                button.prop('disabled', true).text('{{ __('Detecting...') }}');
                const mapSelectors = {
                    inputSelector: '#searchInput',
                    citySelector: '#city',
                    countrySelector: '#country',
                    stateSelector: '#state',
                    addressSelector: '#address',
                    latitudeSelector: '#latitude',
                    longitudeSelector: '#longitude'
                };

                beginGpsLocationApply();
                clearAreaSubAreaForGps(wrapper);

                const bootMap = (window.ensureBackendPlacesMapReady || window.initBackendPlacesMap)
                    ? (window.ensureBackendPlacesMapReady || window.initBackendPlacesMap)(mapSelectors)
                    : Promise.resolve(null);

                Promise.resolve(bootMap)
                    .then(function () {
                        return requestAreaListingPosition();
                    })
                    .then(function (position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        wrapper.find('.area-listing-source').val('browser');
                        wrapper.find('.area-listing-verified').val(position.coords.accuracy && position.coords.accuracy <= 100 ? 1 : 0);

                        const accuracyM = Number(position.coords.accuracy || 0);
                        if (accuracyM > 500) {
                            const rounded = Math.round(accuracyM);
                            areaListingNotify(
                                '{{ __('GPS accuracy is low on desktop') }} (' + rounded + 'm).\n\n'
                                + '{{ __('Your location may be incorrect.') }}\n\n'
                                + '{{ __('Please drag the map pin to the correct property location.') }}\n\n'
                                + '{{ __('Area will update automatically after you move the pin.') }}',
                                'warning',
                                { multiline: true }
                            );
                        }

                        return reverseGeocodeForAreaListing(wrapper, lat, lng, {
                            skipAreaAutoFill: accuracyM > 1000,
                            accuracyM: accuracyM
                        });
                    })
                    .catch(function (error) {
                        areaListingNotify((error && error.message) ? error.message : '{{ __('Unable to get current location.') }}', 'danger');
                    })
                    .finally(function () {
                        setTimeout(function () {
                            endGpsLocationApply();
                            button.prop('disabled', false).text('{{ __('Get Current Location') }}');
                        }, 100);
                    });
            });

            $('.area-listing-admin-state').each(function () {
                const wrapper = areaListingWrapper(this);
                initAreaListingSelect2(wrapper);
                syncSelects(wrapper);
                refreshDetectedAreaInfo(wrapper);
            });

            function bootAreaListingSelects() {
                $('.area-listing-admin-state').each(function () {
                    const wrapper = areaListingWrapper(this);
                    initAreaListingSelect2(wrapper);
                    syncSelects(wrapper);
                });
            }

            [100, 600, 1600, 3200].forEach(function (delay) {
                setTimeout(bootAreaListingSelects, delay);
            });

            $(document).on('click', '.area-listing-edit-detected-area', function () {
                const wrapper = areaListingWrapper(this);
                const input = wrapper.find('#detected_area_name_input, .area-listing-detected-area').first();
                const updated = promptEditDetectedName(input.val(), '{{ __('Detected area name') }}');
                if (updated !== input.val()) {
                    input.val(updated);
                    window.__detectedAreaName = updated;
                    refreshDetectedAreaInfo(wrapper);
                }
            });

            $(document).on('click', '.area-listing-edit-detected-sub-area', function () {
                const wrapper = areaListingWrapper(this);
                const input = wrapper.find('#detected_sub_area_name_input, .area-listing-detected-sub-area').first();
                const updated = promptEditDetectedName(input.val(), '{{ __('Detected sub area name') }}');
                if (updated !== input.val()) {
                    input.val(updated);
                    window.__detectedSubAreaName = updated;
                    refreshDetectedAreaInfo(wrapper);
                }
            });

            $(document).on('area-listing-detected-updated', function () {
                $('.area-listing-admin-area').each(function () {
                    refreshDetectedAreaInfo(areaListingWrapper(this));
                });
            });

            $(document).on('submit', 'form', function () {
                const form = $(this);
                if (!form.find('.area-listing-admin-area').length) {
                    return;
                }
                const wrapper = areaListingWrapper(form.find('.area-listing-admin-area').first());

                form.find('.area-listing-admin-area').each(function () {
                    const value = String($(this).val() || '');
                    if (value.indexOf('__new__:') === 0) {
                        wrapper.find('.area-listing-detected-area').val(titleCase(value.replace('__new__:', '')));
                        $(this).val('');
                    }
                });
                form.find('.area-listing-admin-sub-area').each(function () {
                    const value = String($(this).val() || '');
                    if (value.indexOf('__new__:') === 0) {
                        wrapper.find('.area-listing-detected-sub-area').val(titleCase(value.replace('__new__:', '')));
                        $(this).val('');
                    }
                });

                if (!form.find('input[name="area_listing_admin_save"]').length) {
                    form.append('<input type="hidden" name="area_listing_admin_save" value="1">');
                }
            });
            };

            function scheduleAreaListingBoot() {
                if (window.jQuery) {
                    bootAreaListingAdmin();
                    return;
                }
                var attempts = 0;
                var timer = setInterval(function () {
                    attempts += 1;
                    if (window.jQuery) {
                        clearInterval(timer);
                        bootAreaListingAdmin();
                    } else if (attempts >= 100) {
                        clearInterval(timer);
                    }
                }, 50);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', scheduleAreaListingBoot);
            } else {
                scheduleAreaListingBoot();
            }
        })();
    </script>
@endonce
