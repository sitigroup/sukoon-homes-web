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

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="areas_table" data-toggle="table"
                            data-pagination="true" data-search="true" data-show-refresh="true"
                            data-show-columns="true" data-sort-name="name" data-sort-order="asc"
                            data-responsive="true">
                            <thead class="thead-dark">
                                <tr>
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
                                    <tr>
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

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="sub_areas_table">
                            <thead class="thead-dark">
                                <tr>
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
                                    <tr data-area-id="{{ $subArea->area_id }}">
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
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="thead-dark">
                                <tr>
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
                                @forelse ($pendingSuggestions as $suggestion)
                                    <tr>
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
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">{{ __('No pending suggestions found.') }}</td>
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
                                    <tr class="archived-area-row" data-days-archived="{{ (int) ($archivedArea->days_archived ?? 0) }}">
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
                                        <td colspan="9" class="text-center text-muted">{{ __('No archived areas found for selected city.') }}</td>
                                    </tr>
                                @endforelse
                                @if ($archivedAreas->count())
                                    <tr class="archived-area-empty-filtered d-none">
                                        <td colspan="9" class="text-center text-muted">{{ __('No archived areas match this age filter.') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>

                    <h5 class="mb-3">{{ __('Archived Sub Areas') }}</h5>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="thead-dark">
                                <tr>
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
                                    <tr>
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
                                        <td colspan="7" class="text-center text-muted">{{ __('No archived sub areas found for selected city.') }}</td>
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
@endsection

@section('script')
<script>
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

    if ($('#editAreaCityInput').length) {
        $('#editAreaCityInput').val(city);
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
</script>
@endsection
