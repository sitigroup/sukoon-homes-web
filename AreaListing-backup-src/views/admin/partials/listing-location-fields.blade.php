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
    <select name="state_id" class="form-control select2 area-listing-admin-state" tabindex="-1">
        <option value="">{{ __('Select State') }}</option>
        @foreach ($areaListingStates as $state)
            <option value="{{ $state->id }}" data-name="{{ e($state->name) }}" data-country="{{ e($state->country) }}" @selected((string) $selectedStateId === (string) $state->id)>{{ $state->name }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-6 form-group visually-hidden area-listing-internal-city" aria-hidden="true">
    <label class="form-label col-12">{{ __('City') }}</label>
    <select name="city_id" class="form-control select2 area-listing-admin-city" tabindex="-1" @disabled(empty($selectedStateId))>
        <option value="">{{ __('Select City') }}</option>
        @foreach ($areaListingCities as $city)
            <option value="{{ $city->id }}" data-state-id="{{ $city->state_id }}" data-name="{{ e($city->name) }}" @selected((string) $selectedCityId === (string) $city->id)>{{ $city->name }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-6 form-group">
    <label class="form-label col-12">{{ __('Area') }}</label>
    <select name="area_id" id="area_listing_area_id_{{ $listingType ?? 'listing' }}" class="form-control select2 area-listing-admin-area">
        <option value="">{{ __('Select Area') }}</option>
        @foreach ($areaListingAreas as $area)
            <option value="{{ $area->id }}" data-city-id="{{ $area->city_id }}" data-city="{{ e($area->city) }}" data-state="{{ e($area->state) }}" @selected((string) $selectedAreaId === (string) $area->id)>
                {{ $area->city }} - {{ $area->name }}
            </option>
        @endforeach
    </select>
    <input type="hidden" name="detected_area_name" class="area-listing-detected-area" value="{{ old('detected_area_name', $areaListingCurrent->detected_area_name ?? '') }}" placeholder="{{ __('Manual / detected area') }}">
    <input type="hidden" name="area_listing_source" class="area-listing-source" value="{{ old('area_listing_source', $areaListingCurrent->location_source ?? $areaListingCurrent->source ?? 'admin') }}">
    <input type="hidden" name="location_is_verified" class="area-listing-verified" value="{{ old('location_is_verified', $areaListingCurrent->is_verified ?? 0) }}">
</div>
<div class="col-md-6 form-group">
    <label class="form-label col-12">{{ __('Sub Area') }}</label>
    <select name="sub_area_id" id="area_listing_sub_area_id_{{ $listingType ?? 'listing' }}" class="form-control select2 area-listing-admin-sub-area">
        <option value="">{{ __('Select Sub Area') }}</option>
        @foreach ($areaListingAreas as $area)
            @foreach ($area->subAreas as $subArea)
                <option value="{{ $subArea->id }}" data-area-id="{{ $area->id }}" @selected((string) $selectedSubAreaId === (string) $subArea->id)>
                    {{ $area->name }} - {{ $subArea->name }}
                </option>
            @endforeach
        @endforeach
    </select>
    <input type="hidden" name="detected_sub_area_name" class="area-listing-detected-sub-area" value="{{ old('detected_sub_area_name', $areaListingCurrent->detected_sub_area_name ?? '') }}" placeholder="{{ __('Manual / detected sub-area') }}">
</div>

<div class="col-md-12 form-group">
    <label class="form-label col-12">{{ __('Customer / Manual Address') }}</label>
    <textarea name="manual_address" class="form-control area-listing-manual-address" rows="3" placeholder="{{ __('Type the proper address told by customer') }}">{{ old('manual_address', $areaListingCurrent->manual_address ?? '') }}</textarea>
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
    </style>
    <script>
        (function () {
            function bootAreaListingNativeSync() {
                const normalize = function (value) {
                    return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
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
                    citySelect.disabled = !stateId;
                    areaSelect.disabled = !stateId || (!cityId && !wantedCity);

                    Array.from(areaSelect.options).forEach(function (option) {
                        const optionCityId = option.dataset.cityId || '';
                        const optionCity = normalize(option.dataset.city || '');
                        if (!option.value) {
                            option.disabled = false;
                            return;
                        }
                        if (cityId) {
                            option.disabled = !!optionCityId && optionCityId !== cityId;
                            return;
                        }
                        option.disabled = !!wantedCity && !!optionCity && optionCity !== wantedCity;
                    });

                    const areaId = areaSelect.value || '';
                    subAreaSelect.disabled = !areaId;
                    Array.from(subAreaSelect.options).forEach(function (option) {
                        const optionAreaId = option.dataset.areaId || '';
                        option.disabled = !!optionAreaId && !!areaId && optionAreaId !== areaId;
                    });

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
                    document.querySelectorAll('.area-listing-admin-area').forEach(function (areaSelect) {
                        const root = areaSelect.closest('form') || document;
                        syncOne(root);
                    });
                };

                ['input', 'change', 'blur'].forEach(function (eventName) {
                    document.addEventListener(eventName, function (event) {
                        if (!event.target || !event.target.matches || !event.target.matches('#state, input[name="state"], #city, input[name="city"], #searchInput, input[placeholder="City"], #country, input[name="country"]')) return;
                        syncAll();
                    }, true);
                });

                document.addEventListener('click', function (event) {
                    if (event.target && event.target.closest && event.target.closest('.area-listing-current-location')) {
                        [200, 800, 1600, 3200].forEach(function (delay) {
                            setTimeout(syncAll, delay);
                        });
                    }
                }, true);

                [100, 600, 1600, 3200, 6000].forEach(function (delay) {
                    setTimeout(syncAll, delay);
                });
            }

            const bootAreaListingAdmin = function () {
            bootAreaListingNativeSync();
            if (!window.jQuery) return;

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
                    if (matchedValue || !$(this).val()) return;
                    if (matcher($(this))) matchedValue = $(this).val();
                });
                return matchedValue;
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

                if ($.fn.select2) {
                    stateSelect.add(citySelect).trigger('change.select2');
                }
                wrapper.data('areaListingSyncing', false);
            }

            function syncSelects(wrapper) {
                syncCoreLocationFields(wrapper);

                const stateId = String(wrapper.find('.area-listing-admin-state').val() || '');
                const cityId = String(wrapper.find('.area-listing-admin-city').val() || '');
                const areaId = String(wrapper.find('.area-listing-admin-area').val() || '');
                const cityName = titleCase(wrapper.find('#searchInput, input[placeholder="City"]').val() || wrapper.find('#city, input[name="city"]').val() || '');
                const wantedCity = cityName.toLowerCase().replace(/\s+/g, ' ').trim();

                wrapper.find('.area-listing-admin-city option').each(function () {
                    const optionStateId = String($(this).data('state-id') || '');
                    $(this).prop('disabled', !!optionStateId && !!stateId && optionStateId !== stateId);
                });
                if (wrapper.find('.area-listing-admin-city option:selected').is(':disabled')) {
                    wrapper.find('.area-listing-admin-city').val('');
                }

                wrapper.find('.area-listing-admin-area option').each(function () {
                    const option = $(this);
                    if (!option.val()) return;

                    const optionCityId = String(option.data('city-id') || '');
                    const optionCity = String(option.data('city') || '').toLowerCase().replace(/\s+/g, ' ').trim();
                    if (cityId) {
                        option.prop('disabled', !!optionCityId && optionCityId !== cityId);
                        return;
                    }
                    option.prop('disabled', !!wantedCity && !!optionCity && optionCity !== wantedCity);
                });
                if (wrapper.find('.area-listing-admin-area option:selected').is(':disabled')) {
                    wrapper.find('.area-listing-admin-area').val('');
                }

                wrapper.find('.area-listing-admin-sub-area option').each(function () {
                    const optionAreaId = String($(this).data('area-id') || '');
                    $(this).prop('disabled', !!optionAreaId && !!areaId && optionAreaId !== areaId);
                });
                if (wrapper.find('.area-listing-admin-sub-area option:selected').is(':disabled')) {
                    wrapper.find('.area-listing-admin-sub-area').val('');
                }

                wrapper.find('.area-listing-admin-city').prop('disabled', !stateId);
                wrapper.find('.area-listing-admin-area').prop('disabled', !stateId || (!cityId && !wantedCity));
                wrapper.find('.area-listing-admin-sub-area').prop('disabled', !areaId);

                if ($.fn.select2) {
                    wrapper.find('.area-listing-admin-state, .area-listing-admin-city, .area-listing-admin-area, .area-listing-admin-sub-area').trigger('change.select2');
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
                    select.val(matchedValue).trigger('change');
                    return true;
                }
                return false;
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

                wrapper.find('.area-listing-admin-state, .area-listing-admin-city').each(function () {
                    const select = $(this);
                    const placeholderText = select.hasClass('area-listing-admin-state') ? '{{ __('Select State') }}' : '{{ __('Select City') }}';
                    const emptyOptions = select.find('option[value=""]');
                    emptyOptions.not(':first').remove();
                    emptyOptions.first().text(placeholderText);

                    if (select.hasClass('select2-hidden-accessible')) return;
                    select.select2({
                        width: '100%',
                        theme: 'bootstrap-5',
                        allowClear: true,
                        placeholder: placeholderText
                    });
                });

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
                            if (data.isNew || (data.id && String(data.id).indexOf('__new__:') === 0)) {
                                return $('<span>' + '{{ __('Add') }}' + ' "' + data.text + '"</span>');
                            }
                            return data.text;
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

            function createAdminArea(wrapper, rawName) {
                const citySelect = wrapper.find('.area-listing-admin-city');
                const stateSelect = wrapper.find('.area-listing-admin-state');
                const cityId = String(citySelect.val() || '');
                if (!cityId) {
                    alert('{{ __('Please select city first to add area.') }}');
                    wrapper.find('.area-listing-admin-area').val('').trigger('change');
                    return;
                }

                const name = titleCase(rawName);
                $.ajax({
                    url: '{{ url('area-listing/areas') }}',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        _token: csrfToken(),
                        name: name,
                        state_id: stateSelect.val() || '',
                        city_id: cityId,
                        city: citySelect.find('option:selected').data('name') || wrapper.find('#city, input[name="city"]').val() || '',
                        state: stateSelect.find('option:selected').data('name') || wrapper.find('#state, input[name="state"]').val() || '',
                        country: stateSelect.find('option:selected').data('country') || wrapper.find('#country, input[name="country"]').val() || 'India',
                        center_lat: wrapper.find('#latitude, input[name="latitude"]').val() || '',
                        center_lng: wrapper.find('#longitude, input[name="longitude"]').val() || '',
                    }
                }).done(function (response) {
                    const area = response && response.data ? response.data : null;
                    if (!area || !area.id) return;
                    const option = $('<option></option>')
                        .val(String(area.id))
                        .attr('data-city-id', area.city_id || cityId)
                        .attr('data-city', area.city || (citySelect.find('option:selected').data('name') || ''))
                        .attr('data-state', area.state || (stateSelect.find('option:selected').data('name') || ''))
                        .text((area.city || citySelect.find('option:selected').data('name') || '') + ' - ' + (area.name || name));
                    wrapper.find('.area-listing-admin-area').append(option).val(String(area.id)).trigger('change');
                    wrapper.find('.area-listing-detected-area').val(area.name || name);
                    syncSelects(wrapper);
                }).fail(function (xhr) {
                    const message = xhr && xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ __('Unable to add area.') }}';
                    alert(message);
                    wrapper.find('.area-listing-admin-area').val('').trigger('change');
                });
            }

            function createAdminSubArea(wrapper, rawName) {
                const areaSelect = wrapper.find('.area-listing-admin-area');
                const areaId = String(areaSelect.val() || '');
                if (!areaId || areaId.indexOf('__new__:') === 0) {
                    alert('{{ __('Please select area first to add sub area.') }}');
                    wrapper.find('.area-listing-admin-sub-area').val('').trigger('change');
                    return;
                }

                const name = titleCase(rawName);
                $.ajax({
                    url: '{{ url('area-listing/sub-areas') }}',
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        _token: csrfToken(),
                        area_id: areaId,
                        name: name,
                        center_lat: wrapper.find('#latitude, input[name="latitude"]').val() || '',
                        center_lng: wrapper.find('#longitude, input[name="longitude"]').val() || '',
                    }
                }).done(function (response) {
                    const subArea = response && response.data ? response.data : null;
                    if (!subArea || !subArea.id) return;
                    const areaName = areaSelect.find('option:selected').text().split(' - ').pop().trim();
                    const option = $('<option></option>')
                        .val(String(subArea.id))
                        .attr('data-area-id', areaId)
                        .text((areaName || '{{ __('Area') }}') + ' - ' + (subArea.name || name));
                    wrapper.find('.area-listing-admin-sub-area').append(option).val(String(subArea.id)).trigger('change');
                    wrapper.find('.area-listing-detected-sub-area').val(subArea.name || name);
                    syncSelects(wrapper);
                }).fail(function (xhr) {
                    const message = xhr && xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ __('Unable to add sub area.') }}';
                    alert(message);
                    wrapper.find('.area-listing-admin-sub-area').val('').trigger('change');
                });
            }

            $(document).on('change', '.area-listing-admin-area', function () {
                const wrapper = areaListingWrapper(this);
                const selectedArea = wrapper.find('.area-listing-admin-area option:selected').text().split(' - ').pop().trim();
                wrapper.find('.area-listing-detected-area').val(wrapper.find('.area-listing-admin-area').val() ? selectedArea : '');
            });

            $(document).on('input change blur', '#state, input[name="state"], #city, input[name="city"], #searchInput, input[placeholder="City"], #country, input[name="country"]', function () {
                const wrapper = areaListingWrapper(this);
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
                            if (bestPosition && (!bestPosition.coords.accuracy || bestPosition.coords.accuracy <= 5000)) {
                                resolve(bestPosition);
                            } else {
                                const error = new Error('{{ __('Location accuracy is too low. Please search the address or drag the map pin.') }}');
                                error.accuracy = bestPosition && bestPosition.coords ? bestPosition.coords.accuracy : null;
                                reject(error);
                            }
                        };
                        const timer = setTimeout(finish, 12000);
                        watchId = navigator.geolocation.watchPosition(function (watched) {
                            pickBetter(watched);
                            if (!bestPosition.coords.accuracy || bestPosition.coords.accuracy <= 5000) {
                                clearTimeout(timer);
                                finish();
                            }
                        }, function () {}, { enableHighAccuracy: true, timeout: 25000, maximumAge: 0 });
                    }, reject, { enableHighAccuracy: true, timeout: 25000, maximumAge: 0 });
                });
            }

            $(document).on('click', '.area-listing-current-location', function () {
                const wrapper = areaListingWrapper(this);
                const button = $(this);
                button.prop('disabled', true).text('{{ __('Detecting...') }}');
                requestAreaListingPosition().then(function (position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    wrapper.find('#latitude, input[name="latitude"]').val(lat);
                    wrapper.find('#longitude, input[name="longitude"]').val(lng);
                    wrapper.find('.area-listing-source').val('browser');
                    wrapper.find('.area-listing-verified').val(position.coords.accuracy && position.coords.accuracy <= 100 ? 1 : 0);
                    if (window.google && google.maps && google.maps.Geocoder) {
                        const geocoder = new google.maps.Geocoder();
                        geocoder.geocode({ location: { lat: lat, lng: lng } }, function (results, status) {
                            if (status !== 'OK' || !results || !results[0]) return;
                            const result = results[0];
                            const find = function (types) {
                                const component = result.address_components.find(function (item) {
                                    return types.some(function (type) { return item.types.indexOf(type) !== -1; });
                                });
                                return component ? component.long_name : '';
                            };
                            wrapper.find('#address, textarea[name="address"]').val(result.formatted_address || '');
                            const detectedState = find(['administrative_area_level_1']);
                            const detectedCity = find(['locality', 'administrative_area_level_3']);
                            wrapper.find('#state, input[name="state"]').val(detectedState).trigger('input').trigger('change');
                            wrapper.find('#city, input[name="city"]').val(detectedCity).trigger('input').trigger('change');
                            wrapper.find('#searchInput, input[placeholder="City"]').val(detectedCity).trigger('change').trigger('blur');
                            $('.pac-container').hide();
                            const detectedArea = titleCase(find(['sublocality_level_1', 'neighborhood', 'administrative_area_level_3']));
                            const detectedSubArea = titleCase(find(['sublocality_level_2', 'sublocality_level_3', 'route']));
                            wrapper.find('.area-listing-detected-area').val(detectedArea);
                            wrapper.find('.area-listing-detected-sub-area').val(detectedSubArea);

                            syncSelects(wrapper);
                            selectOptionByText(wrapper.find('.area-listing-admin-area'), function (option) {
                                const label = titleCase(option.text().split(' - ').pop());
                                return detectedArea && label === detectedArea;
                            });
                            syncSelects(wrapper);
                            selectOptionByText(wrapper.find('.area-listing-admin-sub-area'), function (option) {
                                const label = titleCase(option.text().split(' - ').pop());
                                return detectedSubArea && label === detectedSubArea;
                            });
                        });
                    }
                }).catch(function (error) {
                    alert(error.message || '{{ __('Unable to get current location.') }}');
                }).finally(function () {
                    button.prop('disabled', false).text('{{ __('Get Current Location') }}');
                });
            });

            $('.area-listing-admin-state').each(function () {
                const wrapper = areaListingWrapper(this);
                initAreaListingSelect2(wrapper);
                syncSelects(wrapper);
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
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', bootAreaListingAdmin);
            } else {
                bootAreaListingAdmin();
            }
        })();
    </script>
@endonce
