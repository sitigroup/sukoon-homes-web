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
    .area-listing-admin .alert {
        min-height: 39px;
        display: flex;
        align-items: center;
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
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

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
                    <div class="table-responsive mb-4">
                        <table class="table table-striped table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th>{{ __('ID') }}</th>
                                    <th>{{ __('Area') }}</th>
                                    <th>{{ __('City') }}</th>
                                    <th>{{ __('State') }}</th>
                                    <th>{{ __('Country') }}</th>
                                    <th>{{ __('Sub Areas') }}</th>
                                    <th>{{ __('Properties / Projects') }}</th>
                                    <th>{{ __('Archived At') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($archivedAreas as $archivedArea)
                                    <tr>
                                        <td>{{ $archivedArea->id }}</td>
                                        <td>{{ $archivedArea->name }}</td>
                                        <td>{{ $archivedArea->city }}</td>
                                        <td>{{ $archivedArea->state }}</td>
                                        <td>{{ $archivedArea->country }}</td>
                                        <td>{{ $archivedArea->sub_areas_count }}</td>
                                        <td>{{ (int) ($archivedAreaPropertyCounts[$archivedArea->id] ?? 0) }} / {{ (int) ($archivedAreaProjectCounts[$archivedArea->id] ?? 0) }}</td>
                                        <td>{{ $archivedArea->archived_at ? \Illuminate\Support\Carbon::parse($archivedArea->archived_at)->format('d M Y H:i') : '-' }}</td>
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
                                                <div class="alert alert-warning">
                                                    {{ __('Conflicts or invalid rows were found. Fix the CSV and run dry-run again before confirming import.') }}
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
                                        <div class="alert alert-warning">
                                            {{ __('Affected counts changed after preview. Confirm again only if these live counts are acceptable.') }}
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

const activeAreaListingTab = new URLSearchParams(window.location.search).get('tab');
if (activeAreaListingTab) {
    const tab = document.querySelector('#' + activeAreaListingTab + '-tab');
    if (tab && window.bootstrap) {
        new bootstrap.Tab(tab).show();
    }
}
</script>
@endsection
