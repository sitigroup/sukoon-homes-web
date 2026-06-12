@extends('layouts.main')

@section('title'){{ __('Area Wise') }}@endsection

@section('page-title')
<div class="page-title">
    <div class="row">
        <div class="col-12 col-md-6 order-md-1 order-last"><h4>@yield('title')</h4></div>
    </div>
</div>
@endsection

@section('content')
@php
    $areasDataUrl = route('area-listing.areas.data', ['city' => $selectedCity, 'state' => $selectedState, 'country' => $selectedCountry]);
    $subAreasDataUrl = route('area-listing.sub-areas.data', ['city' => $selectedCity, 'state' => $selectedState, 'country' => $selectedCountry]);
    $activeCityParts = array_filter([$selectedCity ?: __('No city selected'), $selectedState, $selectedCountry]);
    $manageCityActive = ($selectedCity ?? '') !== '';
    $managingCityLabel = implode(', ', $activeCityParts);
@endphp
<style>
    .area-listing-admin .table-responsive {
        margin-top: 1rem;
    }
    .area-listing-admin .table th,
    .area-listing-admin .table td {
        vertical-align: middle;
        white-space: nowrap;
    }
    .area-listing-admin .table td:nth-child(3),
    .area-listing-admin .table td:nth-child(4),
    .area-listing-admin .table td:nth-child(5) {
        white-space: normal;
        min-width: 130px;
    }
    .area-listing-admin .btn {
        min-width: 72px;
    }
    .area-listing-admin .btn-sm {
        min-width: auto;
    }
    .area-listing-admin .alert.alert-dismissible {
        min-height: 39px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        padding-right: 2.75rem;
    }
    .area-listing-admin .alert.alert-dismissible .btn-close {
        position: absolute;
        top: 0.65rem;
        right: 0.75rem;
    }
    .area-listing-similar-prompt.alert-dismissible,
    .area-listing-sub-similar-prompt.alert-dismissible {
        position: relative;
        padding-right: 2.75rem;
    }
    .area-listing-admin .active-city-banner {
        background: #fff8e6;
        border: 1px solid #f3d28b;
        color: #5f4300;
        border-radius: 6px;
        padding: 14px 16px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    .area-listing-admin .active-city-banner strong {
        font-weight: 600;
    }
    .area-listing-admin .active-city-banner .btn {
        min-width: 108px;
    }
    .area-listing-admin .managed-city-readonly {
        min-height: 38px;
        display: flex;
        align-items: center;
        background: #f8fafc;
    }
    .area-listing-bulk-bar {
        background: #f8fafc;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 10px 14px;
        margin-bottom: 12px;
    }
    .area-listing-bulk-bar--suggestions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
    }
    .area-listing-bulk-bar--suggestions .suggestions-bulk-reject-note {
        max-width: 180px;
    }
    .area-listing-search-wrap {
        position: relative;
        max-width: 320px;
        margin-bottom: 12px;
    }
    .area-listing-search-wrap .form-control {
        padding-right: 2.25rem;
    }
    .area-listing-search-clear {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        border: 0;
        background: transparent;
        color: #6c757d;
        font-size: 1.25rem;
        line-height: 1;
        padding: 0 4px;
        display: none;
    }
    .area-listing-search-clear:hover {
        color: #212529;
    }
    .area-listing-shortcuts-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        z-index: 2000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .area-listing-shortcuts-panel {
        background: #fff;
        border-radius: 8px;
        max-width: 520px;
        width: 100%;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    }
    .area-listing-suggestion-sub-line {
        padding-left: 1.25rem;
        color: #495057;
        font-size: 0.9rem;
    }
</style>
<section class="section">
    {{-- Flash messages use global Toastify in layouts/footer_script (auto-hide + close). --}}

    <div class="card area-listing-admin">
        <div class="card-body">
            <div class="row align-items-end mb-4">
                <div class="col-md-5 form-group mb-md-0">
                    <label>{{ __('Manage City') }}</label>
                    <select id="areaListingCityFilter" class="form-control">
                        @forelse ($cityOptions as $cityOption)
                            @php
                                $optionCity = (string) $cityOption->city;
                                $optionState = (string) $cityOption->state;
                                $optionCountry = (string) $cityOption->country;
                            @endphp
                            <option value="{{ route('area-listing.index', ['city' => $optionCity, 'state' => $optionState, 'country' => $optionCountry]) }}"
                                @selected($selectedCity === $optionCity && $selectedState === $optionState && $selectedCountry === $optionCountry)>
                                {{ $optionCity }}@if($optionState), {{ $optionState }}@endif
                            </option>
                        @empty
                            <option value="">{{ __('No cities found') }}</option>
                        @endforelse
                    </select>
                    <div class="text-muted small mt-2 area-listing-manage-city-hint">
                        <strong>{{ __('Managing City') }}:</strong>
                        <span id="activeCityBannerText">{{ $managingCityLabel ?: __('No city selected') }}</span>
                    </div>
                </div>
                <div class="col-md-7">
                    <div class="alert alert-light border mb-0">
                        {{ __('Select a city first, then manage only that city areas and sub areas here.') }}
                    </div>
                </div>
            </div>

            <ul class="nav nav-tabs" id="areaWiseTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="areas-tab" data-bs-toggle="tab" data-bs-target="#areas-pane" type="button" role="tab">{{ __('Areas') }}</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="sub-areas-tab" data-bs-toggle="tab" data-bs-target="#sub-areas-pane" type="button" role="tab">{{ __('Sub Areas') }}</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="suggestions-tab" data-bs-toggle="tab" data-bs-target="#suggestions-pane" type="button" role="tab">
                        {{ __('Suggestions') }}
                        @if ($pendingSuggestions->count())
                            <span class="badge bg-warning text-dark ms-1">{{ $pendingSuggestions->count() }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="archived-tab" data-bs-toggle="tab" data-bs-target="#archived-pane" type="button" role="tab">
                        {{ __('Archived') }}
                        @if ($archivedAreas->count() + $archivedSubAreas->count())
                            <span class="badge bg-secondary ms-1">{{ $archivedAreas->count() + $archivedSubAreas->count() }}</span>
                        @endif
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tools-tab" data-bs-toggle="tab" data-bs-target="#tools-pane" type="button" role="tab">{{ __('Tools') }}</button>
                </li>
            </ul>

            <div class="tab-content pt-4">
                <div class="tab-pane fade show active" id="areas-pane" role="tabpanel">
                    <form action="{{ route('area-listing.areas.store') }}" method="POST" class="mb-4">
                        @csrf
                        <div class="row">
                            @if ($manageCityActive)
                                <input type="hidden" name="city" value="{{ $selectedCity }}" required>
                                <input type="hidden" name="state" value="{{ $selectedState }}">
                                <input type="hidden" name="country" value="{{ $selectedCountry ?: 'India' }}">
                                <div class="col-md-8 form-group mandatory">
                                    <label>{{ __('Area') }}</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-4 form-group d-flex align-items-end">
                                    <button class="btn btn-primary w-100" type="submit">{{ __('Save Area') }}</button>
                                </div>
                            @else
                                <div class="col-md-3 form-group mandatory">
                                    <label>{{ __('Area') }}</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-3 form-group mandatory">
                                    <label>{{ __('City') }}</label>
                                    <input type="text" name="city" class="form-control" value="{{ $selectedCity }}" required>
                                </div>
                                <div class="col-md-2 form-group">
                                    <label>{{ __('State') }}</label>
                                    <input type="text" name="state" class="form-control" value="{{ $selectedState }}">
                                </div>
                                <div class="col-md-2 form-group">
                                    <label>{{ __('Country') }}</label>
                                    <input type="text" name="country" class="form-control" value="{{ $selectedCountry ?: 'India' }}">
                                </div>
                                <div class="col-md-2 form-group d-flex align-items-end">
                                    <button class="btn btn-primary w-100" type="submit">{{ __('Save Area') }}</button>
                                </div>
                            @endif
                        </div>
                    </form>

                    <div id="areasBulkBar" class="area-listing-bulk-bar d-none" data-entity="areas">
                        <span class="me-2"><strong class="area-listing-bulk-count">0</strong> {{ __('selected') }}</span>
                        <button type="button" class="btn btn-sm btn-warning area-listing-bulk-archive">{{ __('Bulk Archive') }}</button>
                        <button type="button" class="btn btn-sm btn-danger area-listing-bulk-delete">{{ __('Bulk Force Delete') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary area-listing-bulk-clear">{{ __('Clear Selection') }}</button>
                    </div>
                    <div class="area-listing-search-wrap">
                        <input type="text" id="areasInstantSearch" class="form-control" placeholder="{{ __('Search area') }}" autocomplete="off">
                        <button type="button" class="area-listing-search-clear" data-target="#areasInstantSearch" aria-label="{{ __('Clear') }}">&times;</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="areas_table" data-toggle="table"
                            data-pagination="true" data-search="false" data-show-refresh="true"
                            data-show-columns="true" data-sort-name="name" data-sort-order="asc"
                            data-responsive="true">
                            <thead class="thead-dark">
                                <tr>
                                    <th scope="col" data-field="bulk" data-sortable="false">
                                        <input type="checkbox" id="select-all-areas" class="area-bulk-select-all" title="{{ __('Select all on page') }}">
                                    </th>
                                    <th scope="col" data-field="id" data-sortable="true">{{ __('ID') }}</th>
                                    <th scope="col" data-field="city" data-sortable="true">{{ __('City') }}</th>
                                    <th scope="col" data-field="name" data-sortable="true">{{ __('Area') }}</th>
                                    <th scope="col" data-field="state">{{ __('State') }}</th>
                                    <th scope="col" data-field="country">{{ __('Country') }}</th>
                                    <th scope="col" data-field="sub_areas_count">{{ __('Sub Areas') }}</th>
                                    <th scope="col" data-field="listings_count">{{ __('Properties / Projects') }}</th>
                                    <th scope="col">{{ __('Last Used') }}</th>
                                    <th scope="col" data-field="operate">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($areas as $area)
                                    <tr data-row-id="{{ $area->id }}">
                                        <td><input type="checkbox" class="area-bulk-check" value="{{ $area->id }}"></td>
                                        <td>{{ $area->id }}</td>
                                        <td>{{ $area->city }}</td>
                                        <td>{{ $area->name }}</td>
                                        <td>{{ $area->state }}</td>
                                        <td>{{ $area->country }}</td>
                                        <td>{{ $area->sub_areas_count }}</td>
                                        <td>{{ (int) ($areaPropertyCounts[$area->id] ?? 0) }} / {{ (int) ($areaProjectCounts[$area->id] ?? 0) }}</td>
                                        <td>{{ ! empty($areaLastUsed[$area->id]) ? \Illuminate\Support\Carbon::parse($areaLastUsed[$area->id])->format('d M Y') : '-' }}</td>
                                        <td>@include('area-listing::admin.partials.area-actions', ['area' => $area])</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="sub-areas-pane" role="tabpanel">
                    <form action="{{ route('area-listing.sub-areas.store') }}" method="POST" class="mb-4">
                        @csrf
                        <div class="row">
                            <div class="col-md-3 form-group mandatory">
                                <label>{{ __('Parent Area') }}</label>
                                <select name="area_id" class="form-control" required>
                                    <option value="">{{ __('Select Area') }}</option>
                                    @foreach ($areas as $area)
                                        <option value="{{ $area->id }}">{{ $area->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group mandatory">
                                <label>{{ __('Sub Area Name') }}</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-2 form-group d-flex align-items-end">
                                <button class="btn btn-primary w-100" type="submit">{{ __('Save') }}</button>
                            </div>
                        </div>
                    </form>

                    <div class="row align-items-end mb-3">
                        <div class="col-md-4 form-group mb-md-0">
                            <label>{{ __('Show Sub Areas For') }}</label>
                            <select id="subAreaParentFilter" class="form-control">
                                <option value="">{{ __('All Areas In Selected City') }}</option>
                                @foreach ($areas as $area)
                                    <option value="{{ $area->id }}">{{ $area->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="subAreasBulkBar" class="area-listing-bulk-bar d-none" data-entity="sub-areas">
                        <span class="me-2"><strong class="area-listing-bulk-count">0</strong> {{ __('selected') }}</span>
                        <button type="button" class="btn btn-sm btn-warning area-listing-bulk-archive">{{ __('Bulk Archive') }}</button>
                        <button type="button" class="btn btn-sm btn-danger area-listing-bulk-delete">{{ __('Bulk Force Delete') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary area-listing-bulk-clear">{{ __('Clear Selection') }}</button>
                    </div>
                    <div class="area-listing-search-wrap">
                        <input type="text" id="subAreasInstantSearch" class="form-control" placeholder="{{ __('Search sub area') }}" autocomplete="off">
                        <button type="button" class="area-listing-search-clear" data-target="#subAreasInstantSearch" aria-label="{{ __('Clear') }}">&times;</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="sub_areas_table">
                            <thead class="thead-dark">
                                <tr>
                                    <th scope="col">
                                        <input type="checkbox" id="select-all-sub-areas" class="sub-area-bulk-select-all" title="{{ __('Select all on page') }}">
                                    </th>
                                    <th scope="col">{{ __('ID') }}</th>
                                    <th scope="col">{{ __('City') }}</th>
                                    <th scope="col">{{ __('Area') }}</th>
                                    <th scope="col">{{ __('Sub Area') }}</th>
                                    <th scope="col">{{ __('Properties / Projects') }}</th>
                                    <th scope="col">{{ __('Last Used') }}</th>
                                    <th scope="col">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($subAreas as $subArea)
                                    <tr data-area-id="{{ $subArea->area_id }}" data-row-id="{{ $subArea->id }}">
                                        <td><input type="checkbox" class="sub-area-bulk-check" value="{{ $subArea->id }}"></td>
                                        <td>{{ $subArea->id }}</td>
                                        <td>{{ $subArea->area?->city }}</td>
                                        <td>{{ $subArea->area?->name }}</td>
                                        <td>{{ $subArea->name }}</td>
                                        <td>{{ (int) ($subAreaPropertyCounts[$subArea->id] ?? 0) }} / {{ (int) ($subAreaProjectCounts[$subArea->id] ?? 0) }}</td>
                                        <td>{{ ! empty($subAreaLastUsed[$subArea->id]) ? \Illuminate\Support\Carbon::parse($subAreaLastUsed[$subArea->id])->format('d M Y') : '-' }}</td>
                                        <td>@include('area-listing::admin.partials.sub-area-actions', ['subArea' => $subArea])</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="suggestions-pane" role="tabpanel">
                    <div class="alert alert-light border">
                        {{ __('Review area and sub area suggestions from users/agents before they become available in public filters or listing forms.') }}
                        <span class="d-block small text-muted mt-1">{{ __('Suggestions are removed from the queue with Bulk Reject (not permanent area delete). Use Areas/Sub Areas tabs to force-delete master records.') }}</span>
                    </div>

                    <div id="suggestionsBulkBar" class="area-listing-bulk-bar area-listing-bulk-bar--suggestions d-none" data-entity="suggestions">
                        <span class="me-1"><strong class="area-listing-bulk-count">0</strong> {{ __('selected') }}</span>
                        <button type="button" class="btn btn-sm btn-success area-listing-bulk-approve-all">{{ __('Approve All') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-success area-listing-bulk-approve-area-only" disabled title="{{ __('Only for grouped area + sub area suggestions') }}">{{ __('Approve Area Only') }}</button>
                        <input type="text" id="suggestionsBulkRejectNote" class="form-control form-control-sm suggestions-bulk-reject-note" placeholder="{{ __('Reason required') }}" autocomplete="off">
                        <button type="button" class="btn btn-sm btn-danger area-listing-bulk-reject-all">{{ __('Reject All') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary area-listing-bulk-clear ms-auto">{{ __('Clear Selection') }}</button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="suggestions_table">
                            <thead class="thead-dark">
                                <tr>
                                    <th>
                                        <input type="checkbox" id="select-all-suggestions" class="suggestion-bulk-select-all" title="{{ __('Select all on page') }}">
                                    </th>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Suggested Name') }}</th>
                                    <th>{{ __('City / State') }}</th>
                                    <th>{{ __('Parent Area') }}</th>
                                    <th>{{ __('Suggested By') }}</th>
                                    <th>{{ __('Approve') }}</th>
                                    <th>{{ __('Merge With Existing') }}</th>
                                    <th>{{ __('Reject') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($suggestionDisplayItems ?? [] as $item)
                                    @if (($item['kind'] ?? '') === 'group')
                                        @php
                                            $areaSuggestion = $item['area'] ?? null;
                                            $subSuggestion = $item['sub_area'] ?? null;
                                            $displayRow = $areaSuggestion ?: $subSuggestion;
                                        @endphp
                                        <tr class="suggestion-display-row">
                                            <td>
                                                <input type="checkbox" class="suggestion-bulk-check"
                                                    data-group-token="{{ $item['token'] }}"
                                                    @if ($areaSuggestion) data-has-area-suggestion="1" @endif>
                                            </td>
                                            <td>{{ $displayRow->id ?? '-' }}</td>
                                            <td>{{ __('Area + Sub Area') }}</td>
                                            <td>
                                                <div><strong>{{ $areaSuggestion->name ?? '-' }}</strong></div>
                                                @if ($subSuggestion)
                                                    <div class="area-listing-suggestion-sub-line">↳ {{ __('Sub Area') }}: {{ $subSuggestion->name }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                {{ $displayRow->city ?: ($displayRow->parent_area_city ?? '') }}
                                                @if($displayRow->state || ($displayRow->parent_area_state ?? '')), {{ $displayRow->state ?: $displayRow->parent_area_state }}@endif
                                            </td>
                                            <td>{{ $areaSuggestion->parent_area_name ?? ($subSuggestion->parent_area_name ?? '-') }}</td>
                                            <td>
                                                {{ $displayRow->suggested_by_name ?: __('Unknown') }}
                                                @if(!empty($displayRow->suggested_by_email))
                                                    <div class="text-muted small">{{ $displayRow->suggested_by_email }}</div>
                                                @endif
                                            </td>
                                            <td colspan="3">
                                                <form action="{{ route('area-listing.suggestions.approve-group', $item['token']) }}" method="POST" class="d-inline-block me-1 mb-1">
                                                    @csrf
                                                    <input type="hidden" name="review_note" value="">
                                                    <button type="submit" class="btn btn-sm btn-success">{{ __('Approve All') }}</button>
                                                </form>
                                                @if ($areaSuggestion)
                                                    <form action="{{ route('area-listing.suggestions.approve-area-only', $item['token']) }}" method="POST" class="d-inline-block me-1 mb-1">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-success">{{ __('Approve Area Only') }}</button>
                                                    </form>
                                                @endif
                                                <form action="{{ route('area-listing.suggestions.reject-group', $item['token']) }}" method="POST" class="d-inline-block mb-1">
                                                    @csrf
                                                    <input type="text" name="review_note" class="form-control form-control-sm d-inline-block mb-1" style="max-width:180px" placeholder="{{ __('Reason required') }}" required>
                                                    <button type="submit" class="btn btn-sm btn-danger">{{ __('Reject All') }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @else
                                        @php $suggestion = $item['row']; @endphp
                                        <tr class="suggestion-display-row">
                                            <td><input type="checkbox" class="suggestion-bulk-check" value="{{ $suggestion->id }}"></td>
                                            <td>{{ $suggestion->id }}</td>
                                            <td>{{ $suggestion->type === 'sub_area' ? __('Sub Area') : __('Area') }}</td>
                                            <td>{{ $suggestion->name }}</td>
                                            <td>
                                                {{ $suggestion->city ?: $suggestion->parent_area_city }}
                                                @if($suggestion->state || $suggestion->parent_area_state), {{ $suggestion->state ?: $suggestion->parent_area_state }}@endif
                                            </td>
                                            <td>{{ $suggestion->parent_area_name ?: '-' }}</td>
                                            <td>
                                                {{ $suggestion->suggested_by_name ?: __('Unknown') }}
                                                @if($suggestion->suggested_by_email)
                                                    <div class="text-muted small">{{ $suggestion->suggested_by_email }}</div>
                                                @elseif($suggestion->suggested_by_mobile)
                                                    <div class="text-muted small">{{ $suggestion->suggested_by_mobile }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <form action="{{ route('area-listing.suggestions.approve', $suggestion->id) }}" method="POST">
                                                    @csrf
                                                    <input type="text" name="review_note" class="form-control form-control-sm mb-2" placeholder="{{ __('Optional note') }}">
                                                    <button type="submit" class="btn btn-sm btn-success">{{ __('Approve') }}</button>
                                                </form>
                                            </td>
                                            <td>
                                                <form action="{{ route('area-listing.suggestions.merge', $suggestion->id) }}" method="POST">
                                                    @csrf
                                                    @if ($suggestion->type === 'area')
                                                        <select name="merge_area_id" class="form-control form-control-sm mb-2" required>
                                                            <option value="">{{ __('Select existing area') }}</option>
                                                            @foreach ($mergeAreaOptions as $mergeArea)
                                                                <option value="{{ $mergeArea->id }}">{{ $mergeArea->city }} - {{ $mergeArea->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    @else
                                                        <select name="merge_sub_area_id" class="form-control form-control-sm mb-2" required>
                                                            <option value="">{{ __('Select existing sub area') }}</option>
                                                            @foreach ($mergeSubAreaOptions as $mergeSubArea)
                                                                <option value="{{ $mergeSubArea->id }}">{{ $mergeSubArea->area?->name }} - {{ $mergeSubArea->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    @endif
                                                    <input type="text" name="review_note" class="form-control form-control-sm mb-2" placeholder="{{ __('Optional note') }}">
                                                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Merge') }}</button>
                                                </form>
                                            </td>
                                            <td>
                                                <form action="{{ route('area-listing.suggestions.reject', $suggestion->id) }}" method="POST">
                                                    @csrf
                                                    <input type="text" name="review_note" class="form-control form-control-sm mb-2" placeholder="{{ __('Reason required') }}" required>
                                                    <button type="submit" class="btn btn-sm btn-danger">{{ __('Reject') }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">{{ __('No pending suggestions found.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="archived-pane" role="tabpanel">
                    <div class="alert alert-light border">
                        {{ __('Archived records are hidden from public filters and listing forms. Restore them to make them active again. Permanent delete is allowed only when unused.') }}
                    </div>

                    <h5 class="mb-3">{{ __('Archived Areas') }}</h5>
                    <div id="archivedAreasBulkBar" class="area-listing-bulk-bar d-none" data-entity="archived-areas">
                        <span class="me-2"><strong class="area-listing-bulk-count">0</strong> {{ __('selected') }}</span>
                        <button type="button" class="btn btn-sm btn-success area-listing-bulk-restore">{{ __('Bulk Restore') }}</button>
                        <button type="button" class="btn btn-sm btn-danger area-listing-bulk-delete">{{ __('Bulk Force Delete') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary area-listing-bulk-clear">{{ __('Clear Selection') }}</button>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <span class="text-muted small">{{ __('Show') }}:</span>
                        <div class="btn-group btn-group-sm archived-area-age-filter" role="group" aria-label="{{ __('Archived age filter') }}">
                            <button type="button" class="btn btn-outline-secondary active" data-min-days="0">{{ __('All') }}</button>
                            <button type="button" class="btn btn-outline-secondary" data-min-days="30">{{ __('30+ days') }}</button>
                            <button type="button" class="btn btn-outline-secondary" data-min-days="90">{{ __('90+ days') }}</button>
                        </div>
                    </div>
                    <div class="table-responsive mb-4">
                        <table class="table table-striped table-bordered" id="archived_areas_table">
                            <thead class="thead-dark">
                                <tr>
                                    <th><input type="checkbox" class="archived-area-bulk-select-all" title="{{ __('Select all on page') }}"></th>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Area') }}</th>
                                    <th>{{ __('City') }}</th>
                                    <th>{{ __('State') }}</th>
                                    <th>{{ __('Country') }}</th>
                                    <th>{{ __('Sub Areas') }}</th>
                                    <th>{{ __('Properties / Projects') }}</th>
                                    <th>{{ __('Archived') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($archivedAreas as $archivedArea)
                                    <tr class="archived-area-row" data-days-archived="{{ (int) ($archivedArea->days_archived ?? 0) }}" data-row-id="{{ $archivedArea->id }}">
                                        <td><input type="checkbox" class="archived-area-bulk-check" value="{{ $archivedArea->id }}"></td>
                                        <td>{{ $archivedArea->id }}</td>
                                        <td>{{ $archivedArea->name }}</td>
                                        <td>{{ $archivedArea->city }}</td>
                                        <td>{{ $archivedArea->state }}</td>
                                        <td>{{ $archivedArea->country }}</td>
                                        <td>{{ $archivedArea->sub_areas_count }}</td>
                                        <td>{{ (int) ($archivedAreaPropertyCounts[$archivedArea->id] ?? 0) }} / {{ (int) ($archivedAreaProjectCounts[$archivedArea->id] ?? 0) }}</td>
                                        <td>
                                            {{ $archivedArea->archived_label ?? '-' }}
                                            @if ((int) ($archivedArea->days_archived ?? 0) >= 90)
                                                <span class="badge bg-warning text-dark ms-1">90+ {{ __('days') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <form action="{{ route('area-listing.areas.restore', $archivedArea->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">{{ __('Restore') }}</button>
                                                </form>
                                                <form action="{{ route('area-listing.areas.force-delete', $archivedArea->id) }}" method="POST" class="permanent-delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted">{{ __('No archived areas found for selected city.') }}</td>
                                    </tr>
                                @endforelse
                                @if ($archivedAreas->count())
                                    <tr class="archived-area-empty-filtered d-none">
                                        <td colspan="10" class="text-center text-muted">{{ __('No archived areas match this age filter.') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <h5 class="mb-3">{{ __('Archived Sub Areas') }}</h5>
                    <div id="archivedSubAreasBulkBar" class="area-listing-bulk-bar d-none" data-entity="archived-sub-areas">
                        <span class="me-2"><strong class="area-listing-bulk-count">0</strong> {{ __('selected') }}</span>
                        <button type="button" class="btn btn-sm btn-success area-listing-bulk-restore">{{ __('Bulk Restore') }}</button>
                        <button type="button" class="btn btn-sm btn-danger area-listing-bulk-delete">{{ __('Bulk Force Delete') }}</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary area-listing-bulk-clear">{{ __('Clear Selection') }}</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="archived_sub_areas_table">
                            <thead class="thead-dark">
                                <tr>
                                    <th><input type="checkbox" class="archived-sub-area-bulk-select-all" title="{{ __('Select all on page') }}"></th>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Sub Area') }}</th>
                                    <th>{{ __('Area') }}</th>
                                    <th>{{ __('City') }}</th>
                                    <th>{{ __('Properties / Projects') }}</th>
                                    <th>{{ __('Archived At') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($archivedSubAreas as $archivedSubArea)
                                    <tr data-row-id="{{ $archivedSubArea->id }}">
                                        <td><input type="checkbox" class="archived-sub-area-bulk-check" value="{{ $archivedSubArea->id }}"></td>
                                        <td>{{ $archivedSubArea->id }}</td>
                                        <td>{{ $archivedSubArea->name }}</td>
                                        <td>{{ $archivedSubArea->area?->name ?: '-' }}</td>
                                        <td>{{ $archivedSubArea->area?->city ?: '-' }}</td>
                                        <td>{{ (int) ($archivedSubAreaPropertyCounts[$archivedSubArea->id] ?? 0) }} / {{ (int) ($archivedSubAreaProjectCounts[$archivedSubArea->id] ?? 0) }}</td>
                                        <td>{{ $archivedSubArea->archived_at ? \Illuminate\Support\Carbon::parse($archivedSubArea->archived_at)->format('d M Y H:i') : '-' }}</td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <form action="{{ route('area-listing.sub-areas.restore', $archivedSubArea->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">{{ __('Restore') }}</button>
                                                </form>
                                                <form action="{{ route('area-listing.sub-areas.force-delete', $archivedSubArea->id) }}" method="POST" class="permanent-delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">{{ __('Delete') }}</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">{{ __('No archived sub areas found for selected city.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tools-pane" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-header"><h5>{{ __('Import / Export') }}</h5></div>
                                <div class="card-body">
                                    <form action="{{ route('area-listing.import-csv.dry-run') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <div class="form-group mandatory">
                                            <label>{{ __('CSV File') }}</label>
                                            <input type="file" name="csv_file" accept=".csv,text/csv" class="form-control" required>
                                        </div>
                                        <button class="btn btn-primary" type="submit">{{ __('Run Dry Run') }}</button>
                                        <a href="{{ route('area-listing.export-csv') }}" class="btn btn-outline-primary">{{ __('Export CSV') }}</a>
                                    </form>

                                    @if (session('csv_dry_run'))
                                        @php($csvDryRun = session('csv_dry_run'))
                                        <div class="border rounded p-3 mt-4">
                                            <h6>{{ __('CSV Dry Run Result') }}</h6>
                                            <div class="row mb-3">
                                                <div class="col">{{ __('Rows') }}: {{ $csvDryRun['summary']['total_rows'] ?? 0 }}</div>
                                                <div class="col">{{ __('Will Create') }}: {{ $csvDryRun['summary']['will_create'] ?? 0 }}</div>
                                                <div class="col">{{ __('Duplicates') }}: {{ $csvDryRun['summary']['duplicates_skipped'] ?? 0 }}</div>
                                                <div class="col">{{ __('Conflicts') }}: {{ $csvDryRun['summary']['conflicts'] ?? 0 }}</div>
                                                <div class="col">{{ __('Invalid') }}: {{ $csvDryRun['summary']['invalid'] ?? 0 }}</div>
                                            </div>

                                            @if (! empty($csvDryRun['conflicts']) || ! empty($csvDryRun['invalid']))
                                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                                    {{ __('Conflicts or invalid rows were found. Fix the CSV and run dry-run again before confirming import.') }}
                                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
                                                </div>
                                            @endif

                                            @if (! empty($csvDryRun['will_create']))
                                                <div class="table-responsive mb-3">
                                                    <table class="table table-sm table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>{{ __('Row') }}</th>
                                                                <th>{{ __('Type') }}</th>
                                                                <th>{{ __('City') }}</th>
                                                                <th>{{ __('Area') }}</th>
                                                                <th>{{ __('Sub Area') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($csvDryRun['will_create'] as $item)
                                                                <tr>
                                                                    <td>{{ $item['row'] ?? '-' }}</td>
                                                                    <td>{{ $item['type'] ?? '-' }}</td>
                                                                    <td>{{ $item['city'] ?? '-' }}</td>
                                                                    <td>{{ $item['area_name'] ?? '-' }}</td>
                                                                    <td>{{ $item['sub_area_name'] ?? '-' }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>

                                                <div class="d-flex gap-2">
                                                    <form action="{{ route('area-listing.import-csv.confirm') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="dry_run_token" value="{{ session('csv_dry_run_token') }}">
                                                        <button class="btn btn-success" type="submit">{{ __('Confirm Import') }}</button>
                                                    </form>
                                                    <form action="{{ route('area-listing.import-csv.cancel') }}" method="POST">
                                                        @csrf
                                                        <input type="hidden" name="dry_run_token" value="{{ session('csv_dry_run_token') }}">
                                                        <button class="btn btn-outline-danger" type="submit">{{ __('Cancel Dry Run') }}</button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-header"><h5>{{ __('Normalize') }}</h5></div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">{{ __('Normalizes name casing and safely merges duplicate areas/sub areas while keeping listing links.') }}</p>
                                    <form action="{{ route('area-listing.normalize-all') }}" method="POST" onsubmit="return confirm('{{ __('Normalize and merge duplicate area records?') }}')">
                                        @csrf
                                        <button class="btn btn-warning" type="submit">{{ __('Normalize & Merge Duplicates') }}</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border mt-4" id="areaListingRepairCard">
                        <div class="card-header"><h5>{{ __('Repair Missing Property Location Links') }}</h5></div>
                        <div class="card-body">
                            <p class="text-muted mb-3">
                                {{ __('Properties saved before Area Wise was installed, or saved without area data, may be missing location rows. These properties will not appear in area-based search filters.') }}
                            </p>
                            <button type="button" class="btn btn-primary" id="areaListingRepairDryRunBtn">{{ __('Run Dry Run') }}</button>

                            <div id="areaListingRepairDryRunResult" class="mt-4 d-none"></div>
                            <div id="areaListingRepairExecutePanel" class="mt-3 d-none">
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-success" id="areaListingRepairExecuteBtn">{{ __('Execute Repair') }}</button>
                                    <button type="button" class="btn btn-outline-secondary" id="areaListingRepairCancelBtn">{{ __('Cancel') }}</button>
                                </div>
                            </div>
                            <div id="areaListingRepairExecuteResult" class="mt-3 d-none"></div>
                        </div>
                    </div>

                    <div class="card border mt-4" id="areaListingDriftCard">
                        <div class="card-header"><h5>{{ __('City Drift Check') }}</h5></div>
                        <div class="card-body">
                            <p class="text-muted mb-3">
                                {{ __('Check if area snapshot data (city_name / state / country) has drifted from the linked city record. Run this after any WRTeam upgrade or bulk city import.') }}
                            </p>
                            <button type="button" class="btn btn-primary" id="areaListingDriftCheckBtn">{{ __('Check Drift') }}</button>

                            <div id="areaListingDriftCheckResult" class="mt-4 d-none"></div>
                            <div id="areaListingDriftPreviewPanel" class="mt-3 d-none">
                                <button type="button" class="btn btn-outline-primary" id="areaListingDriftPreviewBtn">{{ __('Preview Sync') }}</button>
                            </div>
                            <div id="areaListingDriftPreviewResult" class="mt-4 d-none"></div>
                            <div id="areaListingDriftConfirmPanel" class="mt-3 d-none">
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-success" id="areaListingDriftConfirmBtn">{{ __('Confirm Sync') }}</button>
                                    <button type="button" class="btn btn-outline-secondary" id="areaListingDriftCancelBtn">{{ __('Cancel') }}</button>
                                </div>
                            </div>
                            <div id="areaListingDriftSyncResult" class="mt-3 d-none"></div>
                        </div>
                    </div>

                    <div class="card border mt-4">
                        <div class="card-header"><h5>{{ __('Merge Preview') }}</h5></div>
                        <div class="card-body">
                            <form action="{{ route('area-listing.merge.preview') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-3 form-group">
                                        <label>{{ __('Merge Type') }}</label>
                                        <select name="entity_type" id="mergeEntityType" class="form-control" required>
                                            <option value="area">{{ __('Area') }}</option>
                                            <option value="sub_area">{{ __('Sub Area') }}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group merge-area-field">
                                        <label>{{ __('Source Area') }}</label>
                                        <select name="source_id" class="form-control">
                                            <option value="">{{ __('Select source') }}</option>
                                            @foreach ($mergeAreaOptions as $mergeArea)
                                                <option value="{{ $mergeArea->id }}">{{ $mergeArea->id }} - {{ $mergeArea->city_name ?: $mergeArea->city }} - {{ $mergeArea->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group merge-area-field">
                                        <label>{{ __('Target Area') }}</label>
                                        <select name="target_id" class="form-control">
                                            <option value="">{{ __('Select target') }}</option>
                                            @foreach ($mergeAreaOptions as $mergeArea)
                                                <option value="{{ $mergeArea->id }}">{{ $mergeArea->id }} - {{ $mergeArea->city_name ?: $mergeArea->city }} - {{ $mergeArea->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group merge-sub-area-field d-none">
                                        <label>{{ __('Source Sub Area') }}</label>
                                        <select name="source_id" class="form-control" disabled>
                                            <option value="">{{ __('Select source') }}</option>
                                            @foreach ($mergeSubAreaOptions as $mergeSubArea)
                                                <option value="{{ $mergeSubArea->id }}">{{ $mergeSubArea->id }} - {{ $mergeSubArea->area?->name }} - {{ $mergeSubArea->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group merge-sub-area-field d-none">
                                        <label>{{ __('Target Sub Area') }}</label>
                                        <select name="target_id" class="form-control" disabled>
                                            <option value="">{{ __('Select target') }}</option>
                                            @foreach ($mergeSubAreaOptions as $mergeSubArea)
                                                <option value="{{ $mergeSubArea->id }}">{{ $mergeSubArea->id }} - {{ $mergeSubArea->area?->name }} - {{ $mergeSubArea->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group d-flex align-items-end">
                                        <button type="submit" class="btn btn-warning w-100">{{ __('Preview Merge') }}</button>
                                    </div>
                                </div>
                            </form>

                            @if (session('merge_preview'))
                                @php($mergePreview = session('merge_preview'))
                                <div class="border rounded p-3 mt-4">
                                    <h6>{{ __('Merge Preview Result') }}</h6>
                                    @if (! empty($mergePreview['counts_changed']))
                                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                            {{ __('Affected counts changed after preview. Confirm again only if these live counts are acceptable.') }}
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('Close') }}"></button>
                                        </div>
                                    @endif
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>{{ __('Source') }}</strong>
                                            <div>{{ __('ID') }}: {{ $mergePreview['source']['id'] ?? '-' }}</div>
                                            <div>{{ __('Name') }}: {{ $mergePreview['source']['name'] ?? '-' }}</div>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>{{ __('Target') }}</strong>
                                            <div>{{ __('ID') }}: {{ $mergePreview['target']['id'] ?? '-' }}</div>
                                            <div>{{ __('Name') }}: {{ $mergePreview['target']['name'] ?? '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <strong>{{ __('Location') }}:</strong>
                                        {{ $mergePreview['city_state_country']['city'] ?? '-' }},
                                        {{ $mergePreview['city_state_country']['state'] ?? '-' }},
                                        {{ $mergePreview['city_state_country']['country'] ?? '-' }}
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col">{{ __('Sub Areas Affected') }}: {{ $mergePreview['counts']['sub_areas'] ?? 0 }}</div>
                                        <div class="col">{{ __('Properties Affected') }}: {{ $mergePreview['counts']['properties'] ?? 0 }}</div>
                                        <div class="col">{{ __('Projects Affected') }}: {{ $mergePreview['counts']['projects'] ?? 0 }}</div>
                                    </div>
                                    @if (! empty($mergePreview['preview_counts']))
                                        <div class="text-muted mt-2">
                                            {{ __('Previous preview counts') }}:
                                            {{ __('Sub Areas') }} {{ $mergePreview['preview_counts']['sub_areas'] ?? 0 }},
                                            {{ __('Properties') }} {{ $mergePreview['preview_counts']['properties'] ?? 0 }},
                                            {{ __('Projects') }} {{ $mergePreview['preview_counts']['projects'] ?? 0 }}
                                        </div>
                                    @endif
                                    <ul class="mt-3 text-warning">
                                        @foreach (($mergePreview['warnings'] ?? []) as $warning)
                                            <li>{{ $warning }}</li>
                                        @endforeach
                                    </ul>
                                    <form action="{{ route('area-listing.merge.confirm') }}" method="POST" class="mt-3">
                                        @csrf
                                        <input type="hidden" name="entity_type" value="{{ $mergePreview['entity_type'] }}">
                                        <input type="hidden" name="source_id" value="{{ $mergePreview['source']['id'] }}">
                                        <input type="hidden" name="target_id" value="{{ $mergePreview['target']['id'] }}">
                                        <input type="hidden" name="preview_sub_areas" value="{{ $mergePreview['counts']['sub_areas'] ?? 0 }}">
                                        <input type="hidden" name="preview_properties" value="{{ $mergePreview['counts']['properties'] ?? 0 }}">
                                        <input type="hidden" name="preview_projects" value="{{ $mergePreview['counts']['projects'] ?? 0 }}">
                                        @if (! empty($mergePreview['counts_changed']))
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" name="accept_changed_counts" value="1" id="acceptChangedCounts" required>
                                                <label class="form-check-label" for="acceptChangedCounts">
                                                    {{ __('I reviewed the changed live counts and still want to merge.') }}
                                                </label>
                                            </div>
                                        @endif
                                        <button type="submit" class="btn btn-danger">{{ __('Confirm Merge & Archive Source') }}</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="editAreaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="editAreaForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Area') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        @if ($manageCityActive)
                            <input type="hidden" name="city_id" id="editAreaCityIdInput">
                            <input type="hidden" name="city" id="editAreaCityInput" required>
                            <input type="hidden" name="state" id="editAreaStateInput">
                            <input type="hidden" name="country" id="editAreaCountryInput">
                            <div class="col-12 alert alert-light border py-2 mb-3" id="editAreaCityReadonly">
                                <strong>{{ __('City') }}:</strong> <span id="editAreaCityReadonlyText">{{ $managingCityLabel }}</span>
                            </div>
                        @else
                            <div class="col-md-4 form-group mandatory"><label>{{ __('City') }}</label><input type="text" name="city" class="form-control" required></div>
                            <div class="col-md-4 form-group"><label>{{ __('State') }}</label><input type="text" name="state" class="form-control"></div>
                            <div class="col-md-4 form-group"><label>{{ __('Country') }}</label><input type="text" name="country" class="form-control"></div>
                        @endif
                        <div class="col-md-4 form-group mandatory"><label>{{ __('Area Name') }}</label><input type="text" name="name" class="form-control" required></div>
                        <div class="col-md-6 form-group">
                            <label>{{ __('SEO Title') }}</label>
                            <input type="text" name="seo_title" class="form-control" maxlength="255" placeholder="{{ __('Optional page title for /search/ URL') }}">
                        </div>
                        <div class="col-12 form-group">
                            <label>{{ __('SEO Description') }}</label>
                            <textarea name="seo_description" class="form-control" rows="2" maxlength="500" placeholder="{{ __('Optional meta description for /search/ URL') }}"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editSubAreaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="editSubAreaForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Sub Area') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group mandatory">
                            <label>{{ __('Parent Area') }}</label>
                            <select name="area_id" class="form-control" required>
                                @foreach ($areas as $area)
                                    <option value="{{ $area->id }}">{{ $area->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group mandatory"><label>{{ __('Sub Area Name') }}</label><input type="text" name="name" class="form-control" required></div>
                        <div class="col-md-6 form-group">
                            <label>{{ __('SEO Title') }}</label>
                            <input type="text" name="seo_title" class="form-control" maxlength="255" placeholder="{{ __('Optional page title for /search/ URL') }}">
                        </div>
                        <div class="col-12 form-group">
                            <label>{{ __('SEO Description') }}</label>
                            <textarea name="seo_description" class="form-control" rows="2" maxlength="500" placeholder="{{ __('Optional meta description for /search/ URL') }}"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="permanentDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Confirm Permanent Delete') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">{{ __('Permanent delete cannot be undone. This is allowed only for unused records.') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-danger" id="confirmPermanentDeleteBtn">{{ __('Permanent Delete') }}</button>
            </div>
        </div>
    </div>
</div>

<div id="areaListingShortcutsOverlay" class="area-listing-shortcuts-overlay" aria-hidden="true">
    <div class="area-listing-shortcuts-panel" role="dialog" aria-labelledby="areaListingShortcutsTitle">
        <h5 id="areaListingShortcutsTitle" class="mb-3">{{ __('Keyboard Shortcuts') }}</h5>
        <ul class="mb-0 small">
            <li><strong>A</strong> — {{ __('Add Area (Areas tab)') }}</li>
            <li><strong>S</strong> — {{ __('Add Sub Area (Sub Areas tab)') }}</li>
            <li><strong>/</strong> {{ __('or') }} <strong>Ctrl+F</strong> — {{ __('Focus search') }}</li>
            <li><strong>Escape</strong> — {{ __('Close modal / clear search') }}</li>
            <li><strong>Ctrl+A</strong> — {{ __('Select all on page') }}</li>
            <li><strong>Ctrl+Shift+A</strong> — {{ __('Deselect all') }}</li>
            <li><strong>N</strong> / <strong>P</strong> — {{ __('Next / previous tab') }}</li>
            <li><strong>R</strong> — {{ __('Refresh page') }}</li>
            <li><strong>C</strong> — {{ __('Focus Manage City') }}</li>
            <li><strong>Ctrl+S</strong> — {{ __('Save open form') }}</li>
            <li><strong>?</strong> — {{ __('Show this help') }}</li>
        </ul>
        <p class="text-muted small mt-3 mb-0">{{ __('Shortcuts are disabled while typing in a field. Press Escape to close.') }}</p>
    </div>
</div>
@endsection

@section('script')
<script>
const areaListingBulkUrls = {
    areas: {
        archive: @json(route('area-listing.areas.bulk-archive')),
        delete: @json(route('area-listing.areas.bulk-delete')),
        restore: @json(route('area-listing.areas.bulk-restore')),
    },
    subAreas: {
        archive: @json(route('area-listing.sub-areas.bulk-archive')),
        delete: @json(route('area-listing.sub-areas.bulk-delete')),
        restore: @json(route('area-listing.sub-areas.bulk-restore')),
    },
    suggestions: {
        approve: @json(route('area-listing.suggestions.bulk-approve')),
        approveAreaOnly: @json(route('area-listing.suggestions.bulk-approve-area-only')),
        reject: @json(route('area-listing.suggestions.bulk-reject')),
    },
};
const areaListingBulkCsrf = @json(csrf_token());

let pendingPermanentDeleteForm = null;

$(document).on('submit', '.permanent-delete-form', function (event) {
    event.preventDefault();
    pendingPermanentDeleteForm = this;

    const modalElement = document.getElementById('permanentDeleteModal');
    if (modalElement && window.bootstrap) {
        new bootstrap.Modal(modalElement).show();
        return;
    }

    if (confirm('{{ __('Permanent delete cannot be undone. This is allowed only for unused records.') }}')) {
        this.submit();
    }
});

$('#confirmPermanentDeleteBtn').on('click', function () {
    if (!pendingPermanentDeleteForm) {
        return;
    }

    const form = pendingPermanentDeleteForm;
    pendingPermanentDeleteForm = null;
    form.submit();
});

$('#areaListingCityFilter').on('change', function () {
    const targetUrl = $(this).val();
    const selectedText = $(this).find('option:selected').text().trim();
    if (selectedText) {
        const country = '{{ $selectedCountry ?: 'India' }}';
        const cityText = selectedText.includes(country) ? selectedText : selectedText + ', ' + country;
        $('#activeCityBannerText').text(cityText);
    }
    if (targetUrl) {
        window.location.href = targetUrl;
    }
});

$('#subAreaParentFilter').on('change', function () {
    const areaId = $(this).val();

    if ($('#sub_areas_table').data('bootstrap.table')) {
        $('#sub_areas_table').bootstrapTable('filterBy', areaId ? { area_id: Number(areaId) } : {});
        return;
    }

    $('#sub_areas_table tbody tr').each(function () {
        const rowAreaId = String($(this).data('area-id'));
        $(this).toggle(!areaId || rowAreaId === String(areaId));
    });
});

$('#mergeEntityType').on('change', function () {
    const isSubArea = $(this).val() === 'sub_area';

    $('.merge-area-field').toggleClass('d-none', isSubArea)
        .find('select').prop('disabled', isSubArea);
    $('.merge-sub-area-field').toggleClass('d-none', !isSubArea)
        .find('select').prop('disabled', !isSubArea);
});

$(document).on('click', '.edit-area', function () {
    const btn = $(this);
    const form = $('#editAreaForm');
    const city = btn.data('city') || '';
    const state = btn.data('state') || '';
    const country = btn.data('country') || '';
    const cityParts = [city, state, country].filter(Boolean);

    form.attr('action', '{{ url('area-listing/areas') }}/' + btn.data('id'));
    form.find('[name=name]').val(btn.data('name'));
    form.find('[name=seo_title]').val(btn.data('seo-title') || '');
    form.find('[name=seo_description]').val(btn.data('seo-description') || '');

    if ($('#editAreaCityInput').length) {
        $('#editAreaCityInput').val(city);
        $('#editAreaCityIdInput').val(btn.data('city-id') || '');
        $('#editAreaStateInput').val(state);
        $('#editAreaCountryInput').val(country);
        $('#editAreaCityReadonlyText').text(cityParts.length ? cityParts.join(', ') : '{{ $managingCityLabel }}');
    } else {
        form.find('[name=city]').val(city);
        form.find('[name=state]').val(state);
        form.find('[name=country]').val(country);
    }
});

$(document).on('click', '.edit-sub-area', function () {
    const btn = $(this);
    const form = $('#editSubAreaForm');
    form.attr('action', '{{ url('area-listing/sub-areas') }}/' + btn.data('id'));
    form.find('[name=area_id]').val(btn.data('area-id'));
    form.find('[name=name]').val(btn.data('name'));
    form.find('[name=seo_title]').val(btn.data('seo-title') || '');
    form.find('[name=seo_description]').val(btn.data('seo-description') || '');
});

function applyArchivedAreaAgeFilter(minDays) {
    const min = Number(minDays || 0);
    $('#archived_areas_table tbody tr.archived-area-row').each(function () {
        const days = Number($(this).data('days-archived') || 0);
        $(this).toggle(min === 0 || days >= min);
    });
    $('#archived_areas_table tbody tr.archived-area-row:visible').length === 0
        ? $('#archived_areas_table tbody .archived-area-empty-filtered').removeClass('d-none')
        : $('#archived_areas_table tbody .archived-area-empty-filtered').addClass('d-none');
}

$(document).on('click', '.archived-area-age-filter .btn', function () {
    const $btn = $(this);
    $btn.addClass('active').siblings().removeClass('active');
    applyArchivedAreaAgeFilter($btn.data('min-days'));
});

const activeAreaListingTab = new URLSearchParams(window.location.search).get('tab');
if (activeAreaListingTab) {
    const tab = document.querySelector('#' + activeAreaListingTab + '-tab');
    if (tab && window.bootstrap) {
        new bootstrap.Tab(tab).show();
    }
}

const areaListingRepairDryRunUrl = @json(route('area-listing.repair-locations.dry-run'));
const areaListingRepairExecuteUrl = @json(route('area-listing.repair-locations.execute'));
const areaListingRepairCsrf = @json(csrf_token());

function areaListingRepairPost(url) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': areaListingRepairCsrf,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    }).then((response) => response.json().then((body) => ({ ok: response.ok, status: response.status, body })));
}

function areaListingRepairEscape(text) {
    return $('<div>').text(text ?? '').html();
}

function areaListingDismissibleAlert(type, messageHtml, autoHideMs) {
    const hideMs = autoHideMs === false ? 0 : (autoHideMs || 6000);
    const $alert = $('<div class="alert alert-dismissible fade show mb-0" role="alert"></div>').addClass('alert-' + type);
    $alert.append($('<div class="area-listing-alert-body flex-grow-1"></div>').html(messageHtml));
    $alert.append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
    if (hideMs > 0) {
        setTimeout(function () {
            if (!$alert.closest('body').length) {
                return;
            }
            if (window.bootstrap && bootstrap.Alert) {
                bootstrap.Alert.getOrCreateInstance($alert[0]).close();
            } else {
                $alert.remove();
            }
        }, hideMs);
    }
    return $alert;
}

function areaListingShowToast(type, message) {
    if (typeof Toastify === 'function') {
        const colors = {
            success: 'linear-gradient(to right, #00b09b, #96c93d)',
            danger: '#dc3545',
            warning: '#f59e0b',
            info: '#0ea5e9',
        };
        Toastify({
            text: message,
            duration: 6000,
            close: true,
            backgroundColor: colors[type] || colors.info,
        }).showToast();
        return;
    }
    alert(message);
}

function areaListingRepairRenderDryRun(data) {
    const $result = $('#areaListingRepairDryRunResult');
    const $executePanel = $('#areaListingRepairExecutePanel');
    const $executeResult = $('#areaListingRepairExecuteResult');
    $executeResult.addClass('d-none').empty();

    const missing = Number(data.missing || 0);
    let html = '';

    $result.removeClass('d-none').empty();

    if (missing === 0) {
        $result.append(areaListingDismissibleAlert('success', '{{ __('All property location rows are present. No repair needed.') }}', 6000));
        $executePanel.addClass('d-none');
    } else {
        $result.append(areaListingDismissibleAlert('warning', missing + ' {{ __('properties found with missing location rows.') }}', false));
        if (Array.isArray(data.sample) && data.sample.length) {
            const $table = $('<div class="table-responsive mt-3"></div>').html(
                '<table class="table table-sm table-bordered mb-0"><thead><tr><th>{{ __('ID') }}</th><th>{{ __('Title') }}</th></tr></thead><tbody></tbody></table>'
            );
            data.sample.forEach((item) => {
                $table.find('tbody').append(
                    '<tr><td>' + areaListingRepairEscape(item.id) + '</td><td>' + areaListingRepairEscape(item.title) + '</td></tr>'
                );
            });
            $result.append($table);
        }
        $executePanel.removeClass('d-none');
    }
}

$('#areaListingRepairDryRunBtn').on('click', function () {
    const $btn = $(this);
    $btn.prop('disabled', true);
    $('#areaListingRepairExecutePanel').addClass('d-none');
    $('#areaListingRepairExecuteResult').addClass('d-none').empty();

    areaListingRepairPost(areaListingRepairDryRunUrl)
        .then(({ ok, body }) => {
            if (! ok) {
                throw new Error(body.message || 'Dry run failed');
            }
            areaListingRepairRenderDryRun(body);
        })
        .catch((error) => {
            areaListingShowToast('danger', error.message || 'Dry run failed');
            $('#areaListingRepairDryRunResult').removeClass('d-none').empty().append(
                areaListingDismissibleAlert('danger', areaListingRepairEscape(error.message || 'Dry run failed'), 8000)
            );
        })
        .finally(() => $btn.prop('disabled', false));
});

$('#areaListingRepairCancelBtn').on('click', function () {
    $('#areaListingRepairDryRunResult').addClass('d-none').empty();
    $('#areaListingRepairExecutePanel').addClass('d-none');
    $('#areaListingRepairExecuteResult').addClass('d-none').empty();
});

const areaListingDriftCheckUrl = @json(route('area-listing.drift-check'));
const areaListingDriftPreviewUrl = @json(route('area-listing.drift-check.sync-dry-run'));
const areaListingDriftExecuteUrl = @json(route('area-listing.drift-check.sync-execute'));

function areaListingDriftPost(url) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': areaListingRepairCsrf,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    }).then((response) => response.json().then((body) => ({ ok: response.ok, status: response.status, body })));
}

function areaListingDriftFormatLocation(values) {
    if (! values) {
        return '';
    }
    return [values.city_name, values.state, values.country].filter(Boolean).join(', ');
}

function areaListingDriftRenderTable(sample, title) {
    if (! Array.isArray(sample) || ! sample.length) {
        return '';
    }

    let html = title ? '<h6 class="mb-2">' + areaListingRepairEscape(title) + '</h6>' : '';
    html += '<div class="table-responsive"><table class="table table-sm table-bordered mb-0"><thead><tr>';
    html += '<th>{{ __('Area Name') }}</th><th>{{ __('Current') }}</th><th>{{ __('Expected') }}</th></tr></thead><tbody>';
    sample.forEach((item) => {
        html += '<tr><td>' + areaListingRepairEscape(item.area_name) + '</td>';
        html += '<td>' + areaListingRepairEscape(areaListingDriftFormatLocation(item.current)) + '</td>';
        html += '<td>' + areaListingRepairEscape(areaListingDriftFormatLocation(item.expected)) + '</td></tr>';
    });
    html += '</tbody></table></div>';
    return html;
}

function areaListingDriftRenderCheck(data) {
    const total = Number(data.total_drift || 0);
    const snapshot = Number(data.snapshot_drift_count || 0);
    const missing = Number(data.missing_city_id_count || 0);
    let html = '';

    const $target = $('#areaListingDriftCheckResult');
    $target.removeClass('d-none').empty();

    if (total === 0) {
        $target.append(areaListingDismissibleAlert('success', '{{ __('No drift found. All snapshots match.') }}', 6000));
        $('#areaListingDriftPreviewPanel').addClass('d-none');
        $('#areaListingDriftConfirmPanel').addClass('d-none');
        $('#areaListingDriftPreviewResult').addClass('d-none').empty();
    } else {
        $target.append(areaListingDismissibleAlert(
            'warning',
            snapshot + ' {{ __('areas have drifted snapshots.') }} ' + missing + ' {{ __('areas missing city_id.') }}',
            false
        ));
        $target.append($(areaListingDriftRenderTable(data.sample, '{{ __('Sample') }}')));
        $('#areaListingDriftPreviewPanel').toggleClass('d-none', snapshot === 0);
        $('#areaListingDriftConfirmPanel').addClass('d-none');
        $('#areaListingDriftPreviewResult').addClass('d-none').empty();
    }
}

$('#areaListingDriftCheckBtn').on('click', function () {
    const $btn = $(this);
    $btn.prop('disabled', true);
    $('#areaListingDriftPreviewPanel').addClass('d-none');
    $('#areaListingDriftConfirmPanel').addClass('d-none');
    $('#areaListingDriftPreviewResult').addClass('d-none').empty();
    $('#areaListingDriftSyncResult').addClass('d-none').empty();

    areaListingDriftPost(areaListingDriftCheckUrl)
        .then(({ ok, body }) => {
            if (! ok) {
                throw new Error(body.message || 'Drift check failed');
            }
            areaListingDriftRenderCheck(body);
        })
        .catch((error) => {
            areaListingShowToast('danger', error.message || 'Drift check failed');
            $('#areaListingDriftCheckResult').removeClass('d-none').empty().append(
                areaListingDismissibleAlert('danger', areaListingRepairEscape(error.message || 'Drift check failed'), 8000)
            );
        })
        .finally(() => $btn.prop('disabled', false));
});

$('#areaListingDriftPreviewBtn').on('click', function () {
    const $btn = $(this);
    $btn.prop('disabled', true);

    areaListingDriftPost(areaListingDriftPreviewUrl)
        .then(({ ok, body }) => {
            if (! ok) {
                throw new Error(body.message || 'Preview failed');
            }

            const wouldUpdate = Number(body.would_update_count || 0);
            const $preview = $('#areaListingDriftPreviewResult').removeClass('d-none').empty();
            $preview.append(areaListingDismissibleAlert(
                'info',
                '{{ __('Preview only — no database changes were made.') }} ' + wouldUpdate + ' {{ __('snapshots would be updated.') }}',
                false
            ));
            $preview.append($(areaListingDriftRenderTable(body.sample, '{{ __('Planned updates') }}')));
            $('#areaListingDriftConfirmPanel').toggleClass('d-none', wouldUpdate === 0);
        })
        .catch((error) => {
            areaListingShowToast('danger', error.message || 'Preview failed');
            $('#areaListingDriftPreviewResult').removeClass('d-none').empty().append(
                areaListingDismissibleAlert('danger', areaListingRepairEscape(error.message || 'Preview failed'), 8000)
            );
        })
        .finally(() => $btn.prop('disabled', false));
});

$('#areaListingDriftCancelBtn').on('click', function () {
    $('#areaListingDriftPreviewResult').addClass('d-none').empty();
    $('#areaListingDriftConfirmPanel').addClass('d-none');
    $('#areaListingDriftSyncResult').addClass('d-none').empty();
});

$('#areaListingDriftConfirmBtn').on('click', function () {
    if (! confirm('{{ __('Update snapshot fields (city_name, state, country) from linked city records for drifted areas?') }}')) {
        return;
    }

    const $btn = $(this);
    $btn.prop('disabled', true);

    areaListingDriftPost(areaListingDriftExecuteUrl)
        .then(({ ok, body }) => {
            if (! ok) {
                throw new Error(body.message || 'Sync failed');
            }

            const updated = Number(body.updated || 0);
            areaListingShowToast('success', '{{ __('Sync complete.') }} ' + updated + ' {{ __('snapshots updated.') }}');
            $('#areaListingDriftSyncResult').removeClass('d-none').empty().append(
                areaListingDismissibleAlert('info', '{{ __('Sync complete.') }} ' + updated + ' {{ __('snapshots updated.') }}', 6000)
            );
            $('#areaListingDriftConfirmPanel').addClass('d-none');
            $('#areaListingDriftPreviewPanel').addClass('d-none');
            return areaListingDriftPost(areaListingDriftCheckUrl);
        })
        .then((response) => {
            if (response && response.ok) {
                areaListingDriftRenderCheck(response.body);
            }
        })
        .catch((error) => {
            areaListingShowToast('danger', error.message || 'Sync failed');
            $('#areaListingDriftSyncResult').removeClass('d-none').empty().append(
                areaListingDismissibleAlert('danger', areaListingRepairEscape(error.message || 'Sync failed'), 8000)
            );
        })
        .finally(() => $btn.prop('disabled', false));
});

$('#areaListingRepairExecuteBtn').on('click', function () {
    if (! confirm('{{ __('Create missing property location rows for all properties found in the dry run?') }}')) {
        return;
    }

    const $btn = $(this);
    $btn.prop('disabled', true);

    areaListingRepairPost(areaListingRepairExecuteUrl)
        .then(({ ok, body }) => {
            if (! ok) {
                throw new Error(body.message || 'Repair failed');
            }

            const created = Number(body.created || 0);
            const errorCount = Number(body.error_count || 0);
            areaListingShowToast('success', '{{ __('Repair complete.') }} ' + created + ' {{ __('rows created.') }}');
            const $executeResult = $('#areaListingRepairExecuteResult').removeClass('d-none').empty();
            $executeResult.append(areaListingDismissibleAlert(
                'info',
                '{{ __('Repair complete.') }} ' + created + ' {{ __('rows created.') }} ' + errorCount + ' {{ __('errors.') }}',
                6000
            ));
            if (Array.isArray(body.errors) && body.errors.length) {
                const $list = $('<ul class="mt-2 mb-0"></ul>');
                body.errors.forEach((item) => {
                    $list.append('<li>#' + areaListingRepairEscape(item.property_id) + ': ' + areaListingRepairEscape(item.message) + '</li>');
                });
                $executeResult.append($list);
            }
            $('#areaListingRepairExecutePanel').addClass('d-none');
            return areaListingRepairPost(areaListingRepairDryRunUrl);
        })
        .then((response) => {
            if (response && response.ok) {
                areaListingRepairRenderDryRun(response.body);
            }
        })
        .catch((error) => {
            areaListingShowToast('danger', error.message || 'Repair failed');
            $('#areaListingRepairExecuteResult').removeClass('d-none').empty().append(
                areaListingDismissibleAlert('danger', areaListingRepairEscape(error.message || 'Repair failed'), 8000)
            );
        })
        .finally(() => $btn.prop('disabled', false));
});

(function areaListingAdminUi() {
    const tabOrder = ['areas', 'sub-areas', 'suggestions', 'archived', 'tools'];

    function isTypingTarget(el) {
        if (!el) return false;
        const tag = (el.tagName || '').toLowerCase();
        return tag === 'input' || tag === 'textarea' || tag === 'select' || el.isContentEditable;
    }

    function activeTabId() {
        const active = document.querySelector('#areaWiseTabs .nav-link.active');
        return active ? active.id.replace('-tab', '') : 'areas';
    }

    function switchTab(tabId) {
        const btn = document.getElementById(tabId + '-tab');
        if (btn && window.bootstrap) {
            new bootstrap.Tab(btn).show();
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tabId);
            window.history.replaceState({}, '', url.toString());
        }
    }

    function countCheckedInScope(paneSelector, checkClass) {
        const $pane = $(paneSelector);
        if (!$pane.length) {
            return 0;
        }
        return $pane.find(checkClass).filter(function () {
            return this.checked === true;
        }).length;
    }

    function updateBulkBar(barSelector, paneSelector, checkClass) {
        const $bar = $(barSelector);
        if (!$bar.length) {
            return;
        }
        const count = countCheckedInScope(paneSelector, checkClass);
        $bar.toggleClass('d-none', count === 0);
        $bar.find('.area-listing-bulk-count').text(count);
        if (barSelector === '#suggestionsBulkBar' && typeof syncSuggestionsBulkActions === 'function') {
            syncSuggestionsBulkActions();
        }
    }

    function bindBulkTable(config) {
        const { bar, pane, table, checkClass, selectAllClass } = config;
        const $pane = $(pane);
        const $table = $(table);

        const syncBar = function () {
            updateBulkBar(bar, pane, checkClass);
        };

        const visibleRowCheckboxes = function () {
            return $pane.find(checkClass).filter(function () {
                const $row = $(this).closest('tr');
                return $row.length && $row.is(':visible');
            });
        };

        $pane.on('change.areaListingBulk', selectAllClass, function () {
            const checked = $(this).prop('checked');
            visibleRowCheckboxes().prop('checked', checked);
            syncBar();
        });

        $pane.on('change.areaListingBulk click.areaListingBulk', checkClass, function () {
            syncBar();
        });

        $(bar).find('.area-listing-bulk-clear').on('click.areaListingBulk', function () {
            $pane.find(checkClass).prop('checked', false);
            $pane.find(selectAllClass).prop('checked', false);
            syncBar();
        });

        if ($table.length && typeof $table.bootstrapTable === 'function') {
            $table.on('post-body.bs.table page-change.bs.table refresh.bs.table', syncBar);
        }

        syncBar();
    }

    bindBulkTable({ bar: '#areasBulkBar', pane: '#areas-pane', table: '#areas_table', checkClass: '.area-bulk-check', selectAllClass: '#select-all-areas' });
    bindBulkTable({ bar: '#subAreasBulkBar', pane: '#sub-areas-pane', table: '#sub_areas_table', checkClass: '.sub-area-bulk-check', selectAllClass: '#select-all-sub-areas' });
    bindBulkTable({ bar: '#archivedAreasBulkBar', pane: '#archived-pane', table: '#archived_areas_table', checkClass: '.archived-area-bulk-check', selectAllClass: '.archived-area-bulk-select-all' });
    bindBulkTable({ bar: '#archivedSubAreasBulkBar', pane: '#archived-pane', table: '#archived_sub_areas_table', checkClass: '.archived-sub-area-bulk-check', selectAllClass: '.archived-sub-area-bulk-select-all' });
    bindBulkTable({ bar: '#suggestionsBulkBar', pane: '#suggestions-pane', table: '#suggestions_table', checkClass: '.suggestion-bulk-check', selectAllClass: '#select-all-suggestions' });

    function syncSuggestionsBulkActions() {
        const $checked = $('#suggestions-pane .suggestion-bulk-check:checked');
        const hasGroupedWithArea = $checked.filter('[data-has-area-suggestion="1"]').length > 0;
        $('#suggestionsBulkBar .area-listing-bulk-approve-area-only').prop('disabled', !hasGroupedWithArea);
    }

    $('#suggestions-pane').on('change.areaListingSuggestionsBulk', '.suggestion-bulk-check, #select-all-suggestions', syncSuggestionsBulkActions);

    function selectedSuggestionPayload() {
        const ids = [];
        const groupTokens = [];
        $('#suggestions-pane .suggestion-bulk-check:checked').each(function () {
            const token = $(this).data('groupToken');
            if (token) {
                groupTokens.push(String(token));
                return;
            }
            const id = Number($(this).val());
            if (id > 0) {
                ids.push(id);
            }
        });
        return { ids, group_tokens: [...new Set(groupTokens)] };
    }

    function selectedIds(paneSelector, checkClass) {
        const ids = [];
        $(paneSelector).find(checkClass + ':checked').each(function () {
            const id = Number($(this).val());
            if (id > 0) {
                ids.push(id);
            }
        });
        return ids;
    }

    function postBulk(url, ids) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': areaListingBulkCsrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ ids }),
        }).then((r) => r.json());
    }

    function bulkToast(label, data) {
        const parts = [];
        ['archived', 'deleted', 'restored', 'approved', 'rejected'].forEach((key) => {
            if (data[key] !== undefined) parts.push(label + ': ' + data[key]);
        });
        if (data.skipped !== undefined) parts.push('{{ __('Skipped (in use)') }}: ' + data.skipped);
        if (data.errors) parts.push('{{ __('Errors') }}: ' + data.errors);
        areaListingShowToast('success', parts.join(' | '));
    }

    $('#areasBulkBar .area-listing-bulk-archive').on('click', function () {
        const ids = selectedIds('#areas-pane', '.area-bulk-check');
        if (!ids.length) return;
        postBulk(areaListingBulkUrls.areas.archive, ids).then((d) => { bulkToast('{{ __('Archived') }}', d); location.reload(); });
    });
    $('#areasBulkBar .area-listing-bulk-delete').on('click', function () {
        const ids = selectedIds('#areas-pane', '.area-bulk-check');
        if (!ids.length || !confirm('{{ __('Permanently delete selected unused areas?') }}')) return;
        postBulk(areaListingBulkUrls.areas.delete, ids).then((d) => { bulkToast('{{ __('Deleted') }}', d); location.reload(); });
    });
    $('#subAreasBulkBar .area-listing-bulk-archive').on('click', function () {
        const ids = selectedIds('#sub-areas-pane', '.sub-area-bulk-check');
        if (!ids.length) return;
        postBulk(areaListingBulkUrls.subAreas.archive, ids).then((d) => { bulkToast('{{ __('Archived') }}', d); location.reload(); });
    });
    $('#subAreasBulkBar .area-listing-bulk-delete').on('click', function () {
        const ids = selectedIds('#sub-areas-pane', '.sub-area-bulk-check');
        if (!ids.length || !confirm('{{ __('Permanently delete selected unused sub areas?') }}')) return;
        postBulk(areaListingBulkUrls.subAreas.delete, ids).then((d) => { bulkToast('{{ __('Deleted') }}', d); location.reload(); });
    });
    $('#archivedAreasBulkBar .area-listing-bulk-restore').on('click', function () {
        const ids = selectedIds('#archived-pane', '.archived-area-bulk-check');
        if (!ids.length) return;
        postBulk(areaListingBulkUrls.areas.restore, ids).then((d) => { bulkToast('{{ __('Restored') }}', d); location.reload(); });
    });
    $('#archivedAreasBulkBar .area-listing-bulk-delete').on('click', function () {
        const ids = selectedIds('#archived-pane', '.archived-area-bulk-check');
        if (!ids.length || !confirm('{{ __('Permanently delete selected unused archived areas?') }}')) return;
        postBulk(areaListingBulkUrls.areas.delete, ids).then((d) => { bulkToast('{{ __('Deleted') }}', d); location.reload(); });
    });
    $('#archivedSubAreasBulkBar .area-listing-bulk-restore').on('click', function () {
        const ids = selectedIds('#archived-pane', '.archived-sub-area-bulk-check');
        if (!ids.length) return;
        postBulk(areaListingBulkUrls.subAreas.restore, ids).then((d) => { bulkToast('{{ __('Restored') }}', d); location.reload(); });
    });
    $('#archivedSubAreasBulkBar .area-listing-bulk-delete').on('click', function () {
        const ids = selectedIds('#archived-pane', '.archived-sub-area-bulk-check');
        if (!ids.length || !confirm('{{ __('Permanently delete selected unused archived sub areas?') }}')) return;
        postBulk(areaListingBulkUrls.subAreas.delete, ids).then((d) => { bulkToast('{{ __('Deleted') }}', d); location.reload(); });
    });

    function postBulkSuggestions(url, payload) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': areaListingBulkCsrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        }).then((r) => r.json().then((body) => {
            if (!r.ok) {
                throw new Error(body.message || '{{ __('Request failed') }}');
            }
            return body;
        }));
    }

    $('#suggestionsBulkBar .area-listing-bulk-approve-all').on('click', function () {
        const payload = selectedSuggestionPayload();
        if (!payload.ids.length && !payload.group_tokens.length) return;
        postBulkSuggestions(areaListingBulkUrls.suggestions.approve, payload).then((d) => {
            bulkToast('{{ __('Approved') }}', d);
            location.reload();
        }).catch((err) => {
            areaListingShowToast('danger', err.message || '{{ __('Bulk approve failed') }}');
        });
    });

    $('#suggestionsBulkBar .area-listing-bulk-approve-area-only').on('click', function () {
        const payload = selectedSuggestionPayload();
        const tokens = payload.group_tokens.filter((token) => {
            return $('#suggestions-pane .suggestion-bulk-check:checked[data-group-token="' + token + '"][data-has-area-suggestion="1"]').length;
        });
        if (!tokens.length) return;
        postBulkSuggestions(areaListingBulkUrls.suggestions.approveAreaOnly, { group_tokens: tokens }).then((d) => {
            bulkToast('{{ __('Approved') }}', d);
            location.reload();
        }).catch((err) => {
            areaListingShowToast('danger', err.message || '{{ __('Approve area only failed') }}');
        });
    });

    $('#suggestionsBulkBar .area-listing-bulk-reject-all').on('click', function () {
        const payload = selectedSuggestionPayload();
        if (!payload.ids.length && !payload.group_tokens.length) return;
        const $noteInput = $('#suggestionsBulkRejectNote');
        const note = String($noteInput.val() || '').trim();
        if (!note) {
            $noteInput.focus();
            areaListingShowToast('warning', @json(__('Rejection reason is required.')));
            return;
        }
        payload.review_note = note;
        postBulkSuggestions(areaListingBulkUrls.suggestions.reject, payload).then((d) => {
            bulkToast('{{ __('Rejected') }}', d);
            location.reload();
        }).catch((err) => {
            areaListingShowToast('danger', err.message || '{{ __('Bulk reject failed') }}');
        });
    });

    function bindInstantSearch(inputSelector, tableSelector, colIndexes) {
        const $input = $(inputSelector);
        const $table = $(tableSelector);
        const $clear = $input.siblings('.area-listing-search-clear');

        function visibleDataRows() {
            const $wrapper = $table.closest('.bootstrap-table');
            if ($wrapper.length) {
                const $fixedRows = $wrapper.find('.fixed-table-body tbody tr').filter(function () {
                    const $row = $(this);
                    return !$row.find('td[colspan]').length && !$row.hasClass('no-records-found');
                });
                if ($fixedRows.length) {
                    return $fixedRows;
                }
            }
            return $table.find('tbody tr').filter(function () {
                const $row = $(this);
                return !$row.find('td[colspan]').length;
            });
        }

        function rowSearchText($row) {
            let hay = '';
            colIndexes.forEach((i) => {
                hay += ' ' + $row.find('td').eq(i).text();
            });
            return hay.toLowerCase();
        }

        function applyFilter() {
            const q = String($input.val() || '').trim().toLowerCase();
            $clear.toggle(!!q);
            visibleDataRows().each(function () {
                const $row = $(this);
                $row.toggle(!q || rowSearchText($row).indexOf(q) !== -1);
            });
        }

        $input.on('input', applyFilter);
        $clear.on('click', function () {
            $input.val('').trigger('input').focus();
        });
        $input.on('keydown', function (e) {
            if (e.key === 'Escape') {
                $input.val('').trigger('input');
            }
        });

        // Re-apply on page change only (avoid post-body/refresh loops with fixed-columns).
        $table.on('page-change.bs.table', function () {
            if (String($input.val() || '').trim()) {
                applyFilter();
            }
        });
    }

    // Areas: name(3), city(2), state(4), country(5). Sub areas: sub area(4), parent area(3), city(2).
    bindInstantSearch('#areasInstantSearch', '#areas_table', [3, 2, 4, 5]);
    bindInstantSearch('#subAreasInstantSearch', '#sub_areas_table', [4, 3, 2]);

    document.querySelectorAll('#areaWiseTabs [data-bs-toggle="tab"]').forEach(function (tabBtn) {
        tabBtn.addEventListener('shown.bs.tab', function () {
            updateBulkBar('#areasBulkBar', '#areas-pane', '.area-bulk-check');
            updateBulkBar('#subAreasBulkBar', '#sub-areas-pane', '.sub-area-bulk-check');
            updateBulkBar('#suggestionsBulkBar', '#suggestions-pane', '.suggestion-bulk-check');
            updateBulkBar('#archivedAreasBulkBar', '#archived-pane', '.archived-area-bulk-check');
            updateBulkBar('#archivedSubAreasBulkBar', '#archived-pane', '.archived-sub-area-bulk-check');
        });
    });

    const $shortcuts = $('#areaListingShortcutsOverlay');
    function showShortcuts(show) {
        $shortcuts.css('display', show ? 'flex' : 'none').attr('aria-hidden', show ? 'false' : 'true');
    }
    $shortcuts.on('click', function (e) {
        if (e.target === this) showShortcuts(false);
    });

    document.addEventListener('keydown', function (e) {
        if (!document.querySelector('.area-listing-admin')) return;
        if (isTypingTarget(e.target) && e.key !== 'Escape') return;

        const tab = activeTabId();
        if (e.key === '?' && !e.ctrlKey && !e.metaKey) {
            e.preventDefault();
            showShortcuts(true);
            return;
        }
        if (e.key === 'Escape') {
            if ($shortcuts.is(':visible')) {
                showShortcuts(false);
                return;
            }
            const $focused = $(document.activeElement);
            if ($focused.is('#areasInstantSearch, #subAreasInstantSearch')) {
                $focused.val('').trigger('input');
                return;
            }
            $('.modal.show').each(function () {
                bootstrap.Modal.getInstance(this)?.hide();
            });
            return;
        }
        if ((e.key === '/' || ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'f')) && (tab === 'areas' || tab === 'sub-areas')) {
            e.preventDefault();
            $(tab === 'areas' ? '#areasInstantSearch' : '#subAreasInstantSearch').focus();
            return;
        }
        if (e.key.toLowerCase() === 'a' && !e.ctrlKey && tab === 'areas') {
            e.preventDefault();
            $('#areas-pane input[name=name]').first().focus();
            return;
        }
        if (e.key.toLowerCase() === 's' && !e.ctrlKey && tab === 'sub-areas') {
            e.preventDefault();
            $('#sub-areas-pane input[name=name]').first().focus();
            return;
        }
        if (e.key.toLowerCase() === 'c' && !e.ctrlKey) {
            e.preventDefault();
            $('#areaListingCityFilter').focus();
            return;
        }
        if (e.key.toLowerCase() === 'r' && !e.ctrlKey) {
            e.preventDefault();
            location.reload();
            return;
        }
        if (e.key.toLowerCase() === 'n' && !e.ctrlKey) {
            const idx = tabOrder.indexOf(tab);
            if (idx >= 0) switchTab(tabOrder[(idx + 1) % tabOrder.length]);
            return;
        }
        if (e.key.toLowerCase() === 'p' && !e.ctrlKey) {
            const idx = tabOrder.indexOf(tab);
            if (idx >= 0) switchTab(tabOrder[(idx - 1 + tabOrder.length) % tabOrder.length]);
            return;
        }
        if (e.ctrlKey && e.key.toLowerCase() === 'a' && !e.shiftKey) {
            const map = {
                areas: ['#select-all-areas', '#areas_table', '.area-bulk-check', '#areasBulkBar'],
                'sub-areas': ['#select-all-sub-areas', '#sub_areas_table', '.sub-area-bulk-check', '#subAreasBulkBar'],
                suggestions: ['#select-all-suggestions', '#suggestions_table', '.suggestion-bulk-check', '#suggestionsBulkBar'],
                archived: ['.archived-area-bulk-select-all', '#archived_areas_table', '.archived-area-bulk-check', '#archivedAreasBulkBar'],
            };
            if (map[tab]) {
                e.preventDefault();
                $(map[tab][0]).prop('checked', true).trigger('change');
            }
            return;
        }
        if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 'a') {
            e.preventDefault();
            $('.area-listing-bulk-bar:visible .area-listing-bulk-clear').first().trigger('click');
            return;
        }
        if (e.ctrlKey && e.key.toLowerCase() === 's') {
            const $openForm = $('.modal.show form').first();
            if ($openForm.length) {
                e.preventDefault();
                $openForm.find('button[type=submit]').first().trigger('click');
            }
        }
    });
})();
</script>
@endsection
