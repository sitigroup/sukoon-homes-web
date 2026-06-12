@extends('layouts.main')

@section('title'){{ __('Nearby Places') }}@endsection

@section('css')
<style>
.nearby-admin .stat-card { border: 0; border-radius: 12px; box-shadow: 0 2px 12px rgba(15, 23, 42, .06); overflow: hidden; height: 100%; }
.nearby-admin .stat-card .stat-accent { width: 4px; position: absolute; left: 0; top: 0; bottom: 0; }
.nearby-admin .stat-value { font-size: 1.65rem; font-weight: 700; line-height: 1.2; color: #0f172a; }
.nearby-admin .stat-label { font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; font-weight: 600; }
.nearby-admin .hero-strip { background: linear-gradient(135deg, #111827 0%, #1f2937 55%, #374151 100%); border-radius: 14px; color: #fff; }
.nearby-admin .hero-strip .badge-status { background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.25); color: #fff; font-weight: 600; }
.nearby-admin .nav-pills .nav-link { border-radius: 999px; font-weight: 600; color: #475569; padding: .55rem 1.1rem; }
.nearby-admin .nav-pills .nav-link.active { background: #111827; color: #fff; box-shadow: 0 4px 14px rgba(17,24,39,.35); }
.nearby-admin .btn-brand { background: #111827; border-color: #111827; color: #fff; }
.nearby-admin .btn-brand:hover { background: #000; border-color: #000; color: #fff; }
.nearby-admin .btn-outline-brand { border-color: #111827; color: #111827; }
.nearby-admin .btn-outline-brand:hover { background: #111827; border-color: #111827; color: #fff; }
.nearby-admin .alert-ok { background: #111827; border-color: #111827; color: #fff; }
.nearby-admin .section-card { border: 0; border-radius: 14px; box-shadow: 0 2px 14px rgba(15,23,42,.05); }
.nearby-admin .section-card .card-header { background: #f8fafc; border-bottom: 1px solid #e2e8f0; border-radius: 14px 14px 0 0 !important; padding: 1rem 1.25rem; }
.nearby-admin .table thead th { font-size: .72rem; text-transform: uppercase; letter-spacing: .05em; color: #64748b; font-weight: 700; border-bottom-width: 1px; white-space: nowrap; }
.nearby-admin .archived-block { border: 1px dashed #cbd5e1; border-radius: 12px; background: #fafafa; }
.nearby-admin .empty-state { text-align: center; padding: 2.5rem 1rem; color: #64748b; }
.nearby-admin .settings-group-title { font-size: .72rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #64748b; margin-bottom: .75rem; padding-bottom: .35rem; border-bottom: 1px solid #e2e8f0; }
.nearby-admin .settings-panel { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem 1.1rem; margin-bottom: 1rem; }
.nearby-admin .settings-panel-title { font-size: .75rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #475569; margin-bottom: .85rem; }
.nearby-admin .status-pill-active { display:inline-block; background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; border-radius:999px; padding:.25rem .55rem; font-size:.72rem; font-weight:700; }
.nearby-admin .status-pill-inactive { display:inline-block; background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; border-radius:999px; padding:.25rem .55rem; font-size:.72rem; font-weight:700; }
.nearby-admin .review-badge-visible { display:inline-block; background:#111827; color:#fff; border-radius:999px; padding:.2rem .5rem; font-size:.7rem; font-weight:600; text-transform:lowercase; }
.nearby-admin .review-badge-hidden { display:inline-block; background:#fee2e2; color:#991b1b; border:1px solid #fecaca; border-radius:999px; padding:.2rem .5rem; font-size:.7rem; font-weight:600; text-transform:lowercase; }
.nearby-admin #nearbyCategoryModal .modal-dialog { max-width: 760px; margin: 1rem auto; }
.nearby-admin #nearbyCategoryModal .modal-content { overflow: hidden; border-radius: 14px; max-height: calc(100vh - 2rem); display: flex; flex-direction: column; }
.nearby-admin #nearbyCategoryModal #nearbyCategoryForm { display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; }
.nearby-admin #nearbyCategoryModal .modal-header { flex: 0 0 auto; }
.nearby-admin #nearbyCategoryModal .modal-body { padding: 0; flex: 1 1 auto; overflow-y: auto; overflow-x: hidden; min-height: 0; }
.nearby-admin #nearbyCategoryModal .modal-body-inner { padding: 1rem 1.25rem 1.25rem; overflow-x: hidden; }
.nearby-admin #nearbyCategoryModal .modal-body-inner > .row { --bs-gutter-x: .85rem; margin-left: 0; margin-right: 0; }
.nearby-admin #nearbyCategoryModal .modal-body-inner > .row > [class*="col-"] { padding-left: calc(var(--bs-gutter-x) * .5); padding-right: calc(var(--bs-gutter-x) * .5); }
.nearby-admin #nearbyCategoryModal .modal-footer { flex: 0 0 auto; position: static; background: #fff; border-top: 1px solid #e2e8f0; padding: .85rem 1.25rem; z-index: 3; box-shadow: 0 -6px 16px rgba(15,23,42,.04); }
.nearby-admin #nearbyCategoryModal .ttl-hint { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: .65rem .85rem; font-size: .8rem; color: #64748b; line-height: 1.45; }
.nearby-admin .cache-action-row { display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
.nearby-admin .review-scope-note { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: .65rem .85rem; font-size: .82rem; color: #64748b; }
.nearby-admin .category-bulk-bar { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: .65rem 1rem; color: #334155; }
.nearby-admin .category-quick-bar { background: #fffbeb; border-bottom: 1px solid #fde68a; padding: .65rem 1rem; color: #111827; }
.nearby-admin .category-quick-bar span { color: #111827 !important; }
.nearby-admin .quick-edit-input { min-width: 72px; font-size: .78rem; padding: .25rem .45rem; height: auto; }
.nearby-admin tr.nearby-category-row.is-selected { background: #f1f5f9; }
.nearby-admin .nearby-category-check { width: 1rem; height: 1rem; cursor: pointer; }
.nearby-admin .hero-strip { padding: 1rem 1.15rem !important; margin-bottom: 1rem !important; }
.nearby-admin .stat-card .card-body { padding: .75rem 1rem .75rem 1.25rem !important; }
.nearby-admin .stat-value { font-size: 1.35rem; }
.nearby-admin .nav-pills { margin-bottom: 1rem !important; }
.nearby-admin .form-label { font-weight: 600; color: #334155; font-size: .875rem; }
.nearby-admin code { font-size: .8rem; background: #f1f5f9; padding: .15rem .4rem; border-radius: 4px; }
.nearby-admin .category-icon-cell { width: 48px; text-align: center; vertical-align: middle; }
.nearby-admin .category-icon-cell i { font-size: 1rem; color: #475569; width: 1.1rem; text-align: center; }
.nearby-admin .category-icon-cell .nearby-icon-preview { display: block; font-size: 1.1rem; color: #475569; margin: 0 auto .25rem; }
.nearby-admin .category-icon-label { display: block; font-size: .65rem; color: #94a3b8; margin-top: .15rem; line-height: 1.1; word-break: break-all; }
</style>
<link href="{{ url('/assets/extensions/@fortawesome/fontawesome-free/css/v4-shims.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('page-title')
<div class="page-title nearby-admin">
    <div class="row align-items-center">
        <div class="col-12 col-md-8 order-md-1 order-last">
            <h4 class="mb-1">@yield('title')</h4>
            <p class="text-muted mb-0 small">{{ __('Manage Google Nearby Places, categories, cache, and travel-time settings.') }}</p>
        </div>
    </div>
</div>
@endsection

@section('content')
<section class="section nearby-admin">
    {{-- Hero / status strip --}}
    <div class="hero-strip p-4 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-lg-7">
                <h5 class="mb-2 fw-bold">{{ __('Nearby Places Intelligence') }}</h5>
                <p class="mb-0 opacity-90 small">{{ __('Property-scoped Google Places with server-side cache and optional driving travel times.') }}</p>
            </div>
            <div class="col-lg-5 d-flex flex-wrap gap-2 justify-content-lg-end">
                <span class="badge badge-status rounded-pill px-3 py-2">
                    {{ __('Plugin') }}: {{ $pluginEnabled ? __('ON') : __('OFF') }}
                </span>
                <span class="badge badge-status rounded-pill px-3 py-2">
                    {{ __('Quality filter') }}: {{ ($qualityFilterEnabled ?? true) ? __('ON') : __('OFF') }}
                </span>
                <span class="badge badge-status rounded-pill px-3 py-2">
                    {{ __('Travel time') }}: {{ $travelTimeEnabled ? __('ON') : __('OFF') }}
                </span>
                <span class="badge badge-status rounded-pill px-3 py-2">
                    {{ __('API key') }}: {{ $apiKeyConfigured ? __('OK') : __('Missing') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['label' => __('Cached places'), 'value' => number_format($stats['nearby_cache_rows']), 'accent' => '#111827'],
            ['label' => __('Properties cached'), 'value' => number_format($stats['properties_with_cache']), 'accent' => '#0ea5e9'],
            ['label' => __('Travel-time rows'), 'value' => number_format($stats['travel_cache_rows']), 'accent' => '#8b5cf6'],
            ['label' => __('Active categories'), 'value' => number_format($stats['active_categories']), 'accent' => '#d97706'],
        ] as $stat)
        <div class="col-6 col-xl-3">
            <div class="card stat-card position-relative">
                <div class="stat-accent" style="background:{{ $stat['accent'] }}"></div>
                <div class="card-body ps-4 py-3">
                    <div class="stat-label">{{ $stat['label'] }}</div>
                    <div class="stat-value">{{ $stat['value'] }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($stats['expired_nearby_rows'] > 0 || $stats['archived_categories'] > 0)
    <div class="alert alert-warning border-0 shadow-sm mb-4 py-2" role="alert">
        <strong>{{ __('Attention') }}:</strong>
        @if($stats['expired_nearby_rows'] > 0)
            {{ __(':count nearby cache rows are expired and will refresh on next API hit.', ['count' => $stats['expired_nearby_rows']]) }}
        @endif
        @if($stats['archived_categories'] > 0)
            {{ __(' :count archived categor(ies) — restore them from the Categories tab.', ['count' => $stats['archived_categories']]) }}
        @endif
    </div>
    @endif

    {{-- Tabs --}}
    <ul class="nav nav-pills flex-wrap gap-2 mb-4" id="nearbyAdminTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#nearbyTabOverview" type="button">{{ __('Overview') }}</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#nearbyTabSettings" type="button">{{ __('Settings') }}</button></li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#nearbyTabCategories" type="button">
                {{ __('Categories') }}
                @if($stats['archived_categories'] > 0)
                    <span class="badge bg-secondary ms-1">{{ $stats['archived_categories'] }} {{ __('archived') }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#nearbyTabCache" type="button">{{ __('Cache tools') }}</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#nearbyTabReview" type="button">{{ __('Review Places') }}</button></li>
    </ul>

    <div class="tab-content" id="nearbyAdminTabContent">

        {{-- OVERVIEW --}}
        <div class="tab-pane fade show active" id="nearbyTabOverview" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card section-card h-100">
                        <div class="card-header"><h6 class="mb-0 fw-bold">{{ __('Google Places API') }}</h6></div>
                        <div class="card-body">
                            <dl class="row mb-3 small">
                                <dt class="col-sm-4">{{ __('Status') }}</dt>
                                <dd class="col-sm-8">
                                    @if($apiKeyConfigured)
                                        <span class="text-dark fw-semibold">{{ __('Configured') }}</span>
                                        <div class="text-muted mt-1"><code>{{ $apiKeyMasked ?: '••••••••' }}</code></div>
                                    @else
                                        <span class="text-danger fw-semibold">{{ __('Not configured') }}</span>
                                        <div class="text-muted mt-1">{{ __('Set Place API Key in Web Settings.') }}</div>
                                    @endif
                                </dd>
                            </dl>
                            <button type="button" class="btn btn-outline-brand btn-sm" id="nearbyCheckApiKeyBtn">{{ __('Test API connection') }}</button>
                            <span id="nearbyApiKeyStatus" class="ms-2 small"></span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card section-card h-100">
                        <div class="card-header"><h6 class="mb-0 fw-bold">{{ __('Quick preview') }}</h6></div>
                        <div class="card-body">
                            <label class="form-label">{{ __('Property ID') }}</label>
                            <div class="input-group input-group-sm mb-2">
                                <input type="number" class="form-control" id="nearbyPreviewPropertyId" min="1" placeholder="12" value="">
                                <button type="button" class="btn btn-outline-primary" id="nearbyPreviewApiBtn">{{ __('Open API JSON') }}</button>
                            </div>
                            <p class="form-text mb-0">{{ __('Opens the public nearby-places API response for debugging (read-only).') }}</p>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="card section-card">
                        <div class="card-header"><h6 class="mb-0 fw-bold">{{ __('Cache snapshot') }}</h6></div>
                        <div class="card-body">
                            <div class="row g-3 text-center">
                                <div class="col-6 col-md-3"><div class="p-3 rounded bg-light"><div class="fw-bold fs-5">{{ number_format($stats['nearby_cache_rows']) }}</div><div class="small text-muted">{{ __('Place rows') }}</div></div></div>
                                <div class="col-6 col-md-3"><div class="p-3 rounded bg-light"><div class="fw-bold fs-5">{{ number_format($stats['travel_cache_rows']) }}</div><div class="small text-muted">{{ __('Travel rows') }}</div></div></div>
                                <div class="col-6 col-md-3"><div class="p-3 rounded bg-light"><div class="fw-bold fs-5">{{ number_format($stats['expired_nearby_rows']) }}</div><div class="small text-muted">{{ __('Expired rows') }}</div></div></div>
                                <div class="col-6 col-md-3"><div class="p-3 rounded bg-light"><div class="fw-bold fs-5">{{ number_format($stats['archived_categories']) }}</div><div class="small text-muted">{{ __('Archived cats') }}</div></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SETTINGS --}}
        <div class="tab-pane fade" id="nearbyTabSettings" role="tabpanel">
            <div class="card section-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">{{ __('Global settings') }}</h6>
                    <span class="badge {{ $pluginEnabled ? 'bg-dark' : 'bg-secondary' }}">{{ $pluginEnabled ? __('Live') : __('Disabled') }}</span>
                </div>
                <div class="card-body">
                    <form id="nearbySettingsForm">
                        @csrf
                        <div class="settings-panel">
                            <div class="settings-panel-title">{{ __('Core') }}</div>
                            <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Plugin enabled') }}</label>
                                <select name="nearby_places_enabled" class="form-control">
                                    <option value="1" @selected(($settings['nearby_places_enabled'] ?? '1') === '1')>{{ __('Yes') }}</option>
                                    <option value="0" @selected(($settings['nearby_places_enabled'] ?? '1') === '0')>{{ __('No') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Nearby quality filter') }}</label>
                                <select name="nearby_places_quality_filter_enabled" class="form-control">
                                    <option value="1" @selected(($settings['nearby_places_quality_filter_enabled'] ?? '1') === '1')>{{ __('ON') }}</option>
                                    <option value="0" @selected(($settings['nearby_places_quality_filter_enabled'] ?? '1') === '0')>{{ __('OFF') }}</option>
                                </select>
                                <div class="form-text">{{ __('Filters fake Google POIs and blocks unreliable Directions.') }}</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Default radius (m)') }}</label>
                                <input type="number" name="nearby_places_default_radius_m" class="form-control" min="100" max="50000" value="{{ $settings['nearby_places_default_radius_m'] ?? 2000 }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Default max results') }}</label>
                                <input type="number" name="nearby_places_default_max_results" class="form-control" min="1" max="20" value="{{ $settings['nearby_places_default_max_results'] ?? 5 }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Places cache TTL (hours)') }}</label>
                                <input type="number" name="nearby_places_cache_ttl_hours" class="form-control" min="1" max="720" value="{{ $settings['nearby_places_cache_ttl_hours'] ?? 168 }}">
                            </div>
                            </div>
                        </div>
                        <div class="settings-panel">
                            <div class="settings-panel-title">{{ __('Privacy (public website)') }}</div>
                            <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('Show directions button on website') }}</label>
                                <select name="nearby_places_public_directions_enabled" class="form-control">
                                    <option value="0" @selected(($settings['nearby_places_public_directions_enabled'] ?? '0') === '0')>{{ __('No — recommended (hide property location)') }}</option>
                                    <option value="1" @selected(($settings['nearby_places_public_directions_enabled'] ?? '0') === '1')>{{ __('Yes') }}</option>
                                </select>
                                <div class="form-text">{{ __('Directions links use the property as the map origin. Keep OFF so customers only see distance text, not a route to the listing.') }}</div>
                            </div>
                            </div>
                        </div>
                        <div class="settings-panel">
                            <div class="settings-panel-title">{{ __('Travel time (Distance Matrix)') }}</div>
                            <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Travel time enabled') }}</label>
                                <select name="nearby_places_travel_time_enabled" class="form-control">
                                    <option value="1" @selected(($settings['nearby_places_travel_time_enabled'] ?? '0') === '1')>{{ __('Yes') }}</option>
                                    <option value="0" @selected(($settings['nearby_places_travel_time_enabled'] ?? '0') === '0')>{{ __('No') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Travel cache TTL (hours)') }}</label>
                                <input type="number" name="nearby_places_travel_cache_ttl_hours" class="form-control" min="1" max="720" value="{{ $settings['nearby_places_travel_cache_ttl_hours'] ?? 168 }}">
                            </div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="submit" class="btn btn-brand px-4" id="nearbySettingsSaveBtn">{{ __('Save settings') }}</button>
                            <span id="nearbySettingsSaveSpinner" class="spinner-border spinner-border-sm text-dark d-none"></span>
                        </div>
                        <div class="mt-3 d-none" id="nearbySettingsFeedbackWrap">
                            <div id="nearbySettingsFeedback" class="alert mb-0 py-2" role="alert"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- CATEGORIES --}}
        <div class="tab-pane fade" id="nearbyTabCategories" role="tabpanel">
            <div class="card section-card mb-4">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="mb-0 fw-bold">{{ __('Active categories') }} <span class="badge bg-dark">{{ $activeCategories->count() }}</span></h6>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <input type="search" class="form-control form-control-sm" id="nearbyCategorySearch" placeholder="{{ __('Search categories…') }}" style="min-width:200px">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="nearbyQuickEditToggleBtn">{{ __('Quick edit all') }}</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#nearbyBulkCategoryModal">{{ __('Bulk add') }}</button>
                        <button type="button" class="btn btn-brand btn-sm" data-bs-toggle="modal" data-bs-target="#nearbyCategoryModal" id="nearbyAddCategoryBtn">{{ __('Add category') }}</button>
                    </div>
                </div>
                <div id="nearbyCategoryBulkBar" class="category-bulk-bar d-none">
                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                        <span class="small fw-semibold" id="nearbyCategorySelectedCount">0 {{ __('selected') }}</span>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="nearbyBulkEditSelectedBtn">{{ __('Bulk edit selected') }}</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="nearbyBulkArchiveBtn">{{ __('Archive selected') }}</button>
                        </div>
                    </div>
                </div>
                <div id="nearbyQuickEditBar" class="category-quick-bar d-none">
                    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                        <span class="small fw-semibold">{{ __('Quick edit mode') }} — {{ __('edit name, icon, radius, and other visible columns inline, then save all.') }} {{ __('Icon codes:') }} <a href="https://fontawesome.com/icons" target="_blank" rel="noopener noreferrer">fontawesome.com/icons</a></span>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-light" id="nearbyQuickEditCancelBtn">{{ __('Cancel') }}</button>
                            <button type="button" class="btn btn-sm btn-brand" id="nearbyQuickEditSaveBtn">{{ __('Save all changes') }}</button>
                        </div>
                    </div>
                </div>
                <div id="nearbyCategoryFeedbackWrap" class="d-none px-3 pt-3">
                    <div id="nearbyCategoryFeedback" class="alert mb-0 py-2" role="alert"></div>
                </div>
                <div class="card-body table-responsive p-0">
                    <p class="small text-muted px-3 pt-3 mb-0">
                        {{ __('Icons use Font Awesome classes (e.g.') }} <code>fas fa-hospital</code>).
                        {{ __('Browse and copy icon codes from') }}
                        <a href="https://fontawesome.com/icons" target="_blank" rel="noopener noreferrer">fontawesome.com/icons</a>.
                        {{ __('Change via') }} <strong>{{ __('Edit') }}</strong> {{ __('or') }} <strong>{{ __('Quick edit all') }}</strong>.
                    </p>
                    <table class="table table-hover align-middle mb-0" id="nearbyActiveCategoriesTable">
                        <thead class="bg-light">
                            <tr>
                                <th style="width:36px"><input type="checkbox" class="nearby-category-check" id="nearbyCategorySelectAll" title="{{ __('Select all') }}"></th>
                                <th class="category-icon-cell">{{ __('Icon') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Slug') }}</th>
                                <th>{{ __('Google type') }}</th>
                                <th>{{ __('Radius') }}</th>
                                <th>{{ __('Max') }}</th>
                                <th>{{ __('Min rating') }}</th>
                                <th>{{ __('Filter max') }}</th>
                                <th>{{ __('Cache TTL') }}</th>
                                <th>{{ __('Sort') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($activeCategories as $category)
                                <tr class="nearby-category-row" data-category-id="{{ $category->id }}" data-category='@json($category)' data-search="{{ strtolower($category->name.' '.$category->slug.' '.($category->google_place_type ?? '')) }}">
                                    <td><input type="checkbox" class="nearby-category-check nearby-category-row-check" value="{{ $category->id }}"></td>
                                    <td class="category-icon-cell nearby-cell-icon">
                                        @if($category->formattedIconClass())
                                            <i class="{{ $category->formattedIconClass() }}" aria-hidden="true"></i>
                                            @if($category->icon)
                                                <span class="category-icon-label">{{ $category->icon }}</span>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="fw-semibold nearby-cell-name">
                                        <span class="quick-view">{{ $category->name }}</span>
                                    </td>
                                    <td class="nearby-cell-slug"><code>{{ $category->slug }}</code></td>
                                    <td class="nearby-cell-google"><code>{{ $category->google_place_type ?: '—' }}</code></td>
                                    <td class="nearby-cell-radius">{{ number_format($category->default_radius_m) }} m</td>
                                    <td class="nearby-cell-max">{{ $category->max_results }}</td>
                                    <td class="nearby-cell-min-rating">{{ number_format((float) ($category->min_rating ?? 3.5), 1) }}</td>
                                    <td class="nearby-cell-filter-max">{{ $category->max_results_after_filter ?: $category->max_results }}</td>
                                    <td>
                                        @php
                                            $categoryTtlHours = $category->cache_ttl_hours ? (int) $category->cache_ttl_hours : (int) ($settings['nearby_places_cache_ttl_hours'] ?? 168);
                                            $usesGlobalTtl = !$category->cache_ttl_hours;
                                            if ($categoryTtlHours % 24 === 0 && $categoryTtlHours >= 24) {
                                                $categoryTtlLabel = ($categoryTtlHours / 24) . ' ' . __('days');
                                            } else {
                                                $categoryTtlLabel = $categoryTtlHours . ' ' . __('hours');
                                            }
                                        @endphp
                                        @if($usesGlobalTtl)
                                            <span class="text-muted">{{ __('Global') }}</span>
                                            <span class="small text-muted d-block">({{ $categoryTtlLabel }})</span>
                                        @else
                                            <span class="fw-semibold">{{ $categoryTtlLabel }}</span>
                                        @endif
                                    </td>
                                    <td class="nearby-cell-sort">{{ $category->sort_order }}</td>
                                    <td class="nearby-cell-status">
                                        @if($category->is_active)
                                            <span class="status-pill-active quick-view">{{ __('Active') }}</span>
                                        @else
                                            <span class="status-pill-inactive quick-view">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap nearby-row-actions">
                                        <button type="button" class="btn btn-sm btn-outline-primary nearby-edit-category" data-category='@json($category)'>{{ __('Edit') }}</button>
                                        <button type="button" class="btn btn-sm btn-outline-danger nearby-delete-category" data-id="{{ $category->id }}" data-name="{{ $category->name }}">{{ __('Archive') }}</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="13"><div class="empty-state">{{ __('No active categories. Add one to get started.') }}</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ARCHIVED --}}
            <div class="archived-block p-0 overflow-hidden">
                <div class="px-4 py-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-muted">
                        {{ __('Archived categories') }}
                        <span class="badge bg-secondary">{{ $archivedCategories->count() }}</span>
                    </h6>
                    @if($archivedCategories->isNotEmpty())
                        <span class="small text-muted">{{ __('Soft-deleted — restore to show on listings again.') }}</span>
                        <button type="button" class="btn btn-sm btn-outline-primary ms-2" id="nearbyBulkRestoreBtn">{{ __('Restore selected') }}</button>
                    @endif
                </div>
                @if($archivedCategories->isEmpty())
                    <div class="empty-state py-4">{{ __('No archived categories.') }}</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width:36px"><input type="checkbox" class="nearby-category-check" id="nearbyArchivedSelectAll" title="{{ __('Select all') }}"></th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Slug') }}</th>
                                    <th>{{ __('Google type') }}</th>
                                    <th>{{ __('Archived at') }}</th>
                                    <th class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($archivedCategories as $category)
                                    <tr class="text-muted nearby-archived-row" data-category-id="{{ $category->id }}">
                                        <td><input type="checkbox" class="nearby-category-check nearby-archived-check" value="{{ $category->id }}"></td>
                                        <td>{{ $category->name }}</td>
                                        <td><code>{{ $category->slug }}</code></td>
                                        <td><code>{{ $category->google_place_type ?: '—' }}</code></td>
                                        <td>{{ $category->deleted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-brand nearby-restore-category" data-id="{{ $category->id }}" data-name="{{ $category->name }}">{{ __('Restore') }}</button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- CACHE --}}
        <div class="tab-pane fade" id="nearbyTabCache" role="tabpanel">
            <div class="card section-card">
                <div class="card-header"><h6 class="mb-0 fw-bold">{{ __('Cache management') }}</h6></div>
                <div class="card-body">
                    <p class="text-muted small mb-3">{{ __('Refresh fetches new data from Google. Clear removes cached rows only — settings and categories are not affected.') }}</p>
                    <div class="row g-3">
                        <div class="col-lg-4">
                            <label class="form-label">{{ __('Property scope') }}</label>
                            <select id="nearbyCachePropertySelect" class="form-control mb-2">
                                <option value="">{{ __('Choose cached property or type ID below') }}</option>
                                <option value="all">{{ __('All cached properties') }} ({{ count($cachedProperties) }})</option>
                                @foreach($cachedProperties as $cachedProperty)
                                    <option value="{{ $cachedProperty['id'] }}">
                                        #{{ $cachedProperty['id'] }} — {{ $cachedProperty['title'] }} ({{ $cachedProperty['row_count'] }} {{ __('rows') }})
                                    </option>
                                @endforeach
                            </select>
                            <label class="form-label">{{ __('Or Property ID') }}</label>
                            <input type="number" id="nearbyPropertyId" name="nearby_property_id" class="form-control" min="1" step="1" placeholder="12" inputmode="numeric">
                            <div class="form-text">{{ __('Pick from cached list, enter an ID, or choose all cached properties.') }}</div>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">{{ __('Category') }}</label>
                            <select id="nearbyCacheCategoryId" class="form-control">
                                <option value="">{{ __('All categories') }}</option>
                                @foreach($activeCategories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('Optional — limit refresh/clear to one category.') }}</div>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label d-none d-lg-block">&nbsp;</label>
                            <div class="cache-action-row">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="nearbyRefreshPropertyBtn">{{ __('Refresh') }}</button>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="nearbyRefreshAllCachedBtn">{{ __('Refresh all cached') }}</button>
                                <button type="button" class="btn btn-outline-warning btn-sm" id="nearbyClearPropertyBtn">{{ __('Clear property') }}</button>
                                <button type="button" class="btn btn-outline-danger btn-sm" id="nearbyClearAllBtn">{{ __('Clear all cache') }}</button>
                                <span id="nearbyToolsSpinner" class="spinner-border spinner-border-sm text-secondary d-none"></span>
                            </div>
                        </div>
                        <div class="col-12 d-none" id="nearbyToolsFeedbackWrap">
                            <div id="nearbyToolsFeedback" class="alert mb-0 py-3 shadow-sm" role="alert">
                                <div class="d-flex align-items-start gap-2">
                                    <span id="nearbyToolsFeedbackIcon" class="fs-5 lh-1"></span>
                                    <div>
                                        <div id="nearbyToolsFeedbackTitle" class="fw-semibold mb-1"></div>
                                        <div id="nearbyToolsFeedbackMessage" class="mb-0"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-none" id="nearbyQualityPreviewWrap">
                            <div class="card border-0 bg-light mt-2">
                                <div class="card-body p-3">
                                    <h6 class="fw-bold mb-2">{{ __('Quality filter preview') }}</h6>
                                    <div id="nearbyQualityPreviewSummary" class="small text-muted mb-2"></div>
                                    <div id="nearbyQualityPreviewBody" class="small"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-none" id="nearbyToolsDetailsWrap">
                            <details class="small">
                                <summary class="text-muted">{{ __('Technical details') }}</summary>
                                <pre id="nearbyToolsOutput" class="bg-light border rounded p-3 small mt-2 mb-0" style="max-height:160px;overflow:auto;white-space:pre-wrap;"></pre>
                            </details>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- REVIEW PLACES --}}
        <div class="tab-pane fade" id="nearbyTabReview" role="tabpanel">
            <div class="card section-card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h6 class="mb-0 fw-bold">{{ __('Review Places') }}</h6>
                    <span class="small text-muted">{{ __('Admin overrides win over automatic quality filter.') }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3 mb-3 align-items-end">
                        <div class="col-lg-3">
                            <label class="form-label">{{ __('Property scope') }}</label>
                            <select id="nearbyReviewPropertySelect" class="form-control mb-2">
                                <option value="">{{ __('Choose cached property or type ID below') }}</option>
                                <option value="all">{{ __('All cached properties') }} ({{ count($cachedProperties) }})</option>
                                @foreach($cachedProperties as $cachedProperty)
                                    <option value="{{ $cachedProperty['id'] }}">
                                        #{{ $cachedProperty['id'] }} — {{ $cachedProperty['title'] }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" id="nearbyReviewPropertyId" class="form-control" min="1" placeholder="{{ __('Or Property ID') }}">
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label">{{ __('Category') }}</label>
                            <select id="nearbyReviewCategoryId" class="form-control">
                                <option value="">{{ __('All categories') }}</option>
                                @foreach($activeCategories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label">{{ __('Filter') }}</label>
                            <select id="nearbyReviewStatus" class="form-control">
                                <option value="all">{{ __('All') }}</option>
                                <option value="visible">{{ __('Visible') }}</option>
                                <option value="hidden">{{ __('Hidden') }}</option>
                                <option value="force_shown">{{ __('Force shown') }}</option>
                                <option value="force_hidden">{{ __('Force hidden') }}</option>
                                <option value="low_quality">{{ __('Low quality') }}</option>
                                <option value="same_coordinate">{{ __('Same coordinate') }}</option>
                                <option value="unrated">{{ __('Unrated') }}</option>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">{{ __('Search') }}</label>
                            <input type="search" id="nearbyReviewSearch" class="form-control" placeholder="{{ __('Name or place ID') }}">
                        </div>
                        <div class="col-lg-2">
                            <button type="button" class="btn btn-brand w-100" id="nearbyReviewLoadBtn">{{ __('Load review') }}</button>
                        </div>
                    </div>
                    <div class="review-scope-note mb-2">{{ __('Pick one cached property, type any Property ID, or load all cached properties at once.') }}</div>
                    <div id="nearbyReviewSummary" class="small text-muted mb-2"></div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle" id="nearbyReviewTable">
                            <thead class="bg-light">
                                <tr id="nearbyReviewTableHead">
                                    <th class="d-none" id="nearbyReviewPropertyCol">{{ __('Property') }}</th>
                                    <th>{{ __('Place') }}</th>
                                    <th>{{ __('Category') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Score') }}</th>
                                    <th>{{ __('Distance') }}</th>
                                    <th>{{ __('Rating') }}</th>
                                    <th>{{ __('Reason') }}</th>
                                    <th class="text-end">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody id="nearbyReviewTableBody">
                                <tr><td colspan="9" class="text-center text-muted py-4">{{ __('Choose a property scope to review nearby places.') }}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Category modal --}}
<div class="modal fade" id="nearbyCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="nearbyCategoryForm">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold" id="nearbyCategoryModalTitle">{{ __('Add category') }}</h5>
                        <p class="text-muted small mb-0">{{ __('Maps to a Google Places type for nearby search.') }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="modal-body-inner">
                    <div class="row g-3">
                    <input type="hidden" name="category_id" id="nearbyCategoryId">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Name') }} *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Slug') }}</label>
                        <input type="text" name="slug" class="form-control" placeholder="hospital">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Google place type') }}</label>
                        <input type="text" name="google_place_type" class="form-control" placeholder="hospital">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Icon class') }}</label>
                        <input type="text" name="icon" class="form-control" placeholder="fas fa-hospital">
                        <div class="form-text">
                            {{ __('Font Awesome class — browse icons at') }}
                            <a href="https://fontawesome.com/icons" target="_blank" rel="noopener noreferrer">fontawesome.com/icons</a>
                            {{ __('(e.g.') }} <code>fas fa-hospital</code>).
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Radius (m)') }} *</label>
                        <input type="number" name="default_radius_m" class="form-control" min="100" max="50000" value="2000" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Max results') }} *</label>
                        <input type="number" name="max_results" class="form-control" min="1" max="20" value="5" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Sort order') }}</label>
                        <input type="number" name="sort_order" class="form-control" min="0" value="0">
                    </div>
                    <div class="col-12"><div class="settings-group-title">{{ __('Cache TTL') }}</div></div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Cache TTL value') }}</label>
                        <input type="number" name="cache_ttl_value" id="nearbyCategoryCacheTtlValue" class="form-control" min="1" max="8760" placeholder="{{ __('Use global TTL') }}">
                        <div class="form-text">{{ __('Leave empty to use global places cache TTL.') }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Cache TTL unit') }}</label>
                        <select name="cache_ttl_unit" id="nearbyCategoryCacheTtlUnit" class="form-control">
                            <option value="days">{{ __('Days') }}</option>
                            <option value="hours">{{ __('Hours') }}</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <div class="ttl-hint">
                            <strong>{{ __('Suggested defaults') }}:</strong>
                            {{ __('School/Hospital/Park/Bank/Bus/Railway: 365 days · Restaurant/Gym/Market: 184 days') }}
                        </div>
                    </div>
                    <div class="col-12"><div class="settings-group-title">{{ __('Quality filter') }}</div></div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Minimum rating') }}</label>
                        <input type="number" name="min_rating" class="form-control" min="0" max="5" step="0.1" value="3.5">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Minimum reviews') }}</label>
                        <input type="number" name="min_reviews" class="form-control" min="0" max="10000" value="3">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Allow unrated places') }}</label>
                        <select name="allow_unrated" class="form-control">
                            <option value="0">{{ __('No') }}</option>
                            <option value="1">{{ __('Yes') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('Max results after filter') }}</label>
                        <input type="number" name="max_results_after_filter" class="form-control" min="1" max="20" placeholder="{{ __('Same as max results') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Max distance (m)') }}</label>
                        <input type="number" name="max_distance_m" class="form-control" min="100" max="50000" placeholder="{{ __('Use category radius') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Hide generic places') }}</label>
                        <select name="hide_generic_places" class="form-control">
                            <option value="1" selected>{{ __('Yes') }}</option>
                            <option value="0">{{ __('No') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Hide suspicious same-location') }}</label>
                        <select name="hide_suspicious_same_location" class="form-control">
                            <option value="1" selected>{{ __('Yes') }}</option>
                            <option value="0">{{ __('No') }}</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('Visible on frontend') }}</label>
                        <select name="is_active" class="form-control">
                            <option value="1">{{ __('Yes') }}</option>
                            <option value="0">{{ __('No') }}</option>
                        </select>
                    </div>
                    <div class="col-12 d-none" id="nearbyCategoryFormFeedbackWrap">
                        <div id="nearbyCategoryFormFeedback" class="alert mb-0 py-2" role="alert"></div>
                    </div>
                    </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-brand" id="nearbyCategorySaveBtn">{{ __('Save category') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Bulk add categories --}}
<div class="modal fade" id="nearbyBulkCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">{{ __('Bulk add categories') }}</h5>
                    <p class="text-muted small mb-0">{{ __('One category per line. Use commas or pipes between fields.') }}</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">{{ __('Category lines') }}</label>
                <textarea id="nearbyBulkCategoryInput" class="form-control font-monospace" rows="10" placeholder="Hospital, hospital, fa-hospital, 2000, 5, 1&#10;School, school, fa-school, 2000, 5, 2"></textarea>
                <div class="form-text mt-2">
                    {{ __('Format') }}: <code>{{ __('Name, Google type, Icon, Radius (m), Max results, Sort order') }}</code>.
                    {{ __('Icon: Font Awesome class from') }}
                    <a href="https://fontawesome.com/icons" target="_blank" rel="noopener noreferrer">fontawesome.com/icons</a>.
                    {{ __('Missing values use global defaults.') }}
                </div>
                <div class="ttl-hint mt-3">
                    <strong>{{ __('Example') }}:</strong>
                    <code>Restaurant, restaurant, fa-utensils, 2000, 5, 3</code>
                </div>
                <div id="nearbyBulkCategoryPreview" class="small text-muted mt-3"></div>
                <div id="nearbyBulkCategoryFeedbackWrap" class="d-none mt-3">
                    <div id="nearbyBulkCategoryFeedback" class="alert mb-0 py-2" role="alert"></div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-brand" id="nearbyBulkCategorySubmitBtn">{{ __('Add categories') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Bulk edit selected --}}
<div class="modal fade" id="nearbyBulkEditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold">{{ __('Bulk edit selected categories') }}</h5>
                    <p class="text-muted small mb-0" id="nearbyBulkEditSummary">{{ __('Apply the same field values to all selected categories.') }}</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">{{ __('Radius (m)') }}</label>
                    <input type="number" id="nearbyBulkEditRadius" class="form-control" min="100" max="50000" placeholder="{{ __('Leave blank to skip') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Max results') }}</label>
                    <input type="number" id="nearbyBulkEditMax" class="form-control" min="1" max="20" placeholder="{{ __('Leave blank to skip') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Min rating') }}</label>
                    <input type="number" id="nearbyBulkEditMinRating" class="form-control" min="0" max="5" step="0.1" placeholder="{{ __('Leave blank to skip') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Filter max') }}</label>
                    <input type="number" id="nearbyBulkEditFilterMax" class="form-control" min="1" max="20" placeholder="{{ __('Leave blank to skip') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Sort order') }}</label>
                    <input type="number" id="nearbyBulkEditSort" class="form-control" min="0" max="9999" placeholder="{{ __('Leave blank to skip') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Status') }}</label>
                    <select id="nearbyBulkEditStatus" class="form-control">
                        <option value="">{{ __('Leave unchanged') }}</option>
                        <option value="1">{{ __('Active') }}</option>
                        <option value="0">{{ __('Inactive') }}</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Cache TTL value') }}</label>
                    <input type="number" id="nearbyBulkEditCacheValue" class="form-control" min="1" max="8760" placeholder="{{ __('Leave blank to skip') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Cache TTL unit') }}</label>
                    <select id="nearbyBulkEditCacheUnit" class="form-control">
                        <option value="days">{{ __('Days') }}</option>
                        <option value="hours">{{ __('Hours') }}</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-brand" id="nearbyBulkEditSubmitBtn">{{ __('Apply to selected') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const apiBase = @json(rtrim(config('app.url'), '/'));
    const toolsOutput = document.getElementById('nearbyToolsOutput');
    const toolsSpinner = document.getElementById('nearbyToolsSpinner');
    const categoryModalEl = document.getElementById('nearbyCategoryModal');
    const categoryModal = categoryModalEl ? new bootstrap.Modal(categoryModalEl) : null;
    const categoryForm = document.getElementById('nearbyCategoryForm');

    const TAB_KEYS = {
        '#nearbyTabOverview': 'overview',
        '#nearbyTabSettings': 'settings',
        '#nearbyTabCategories': 'categories',
        '#nearbyTabCache': 'cache',
        '#nearbyTabReview': 'review',
    };

    function saveAdminTab(tabKey) {
        try { sessionStorage.setItem('nearbyAdminActiveTab', tabKey); } catch (e) {}
        const url = window.location.pathname + window.location.search + '#' + tabKey;
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', url);
        }
    }

    function activateAdminTab(target) {
        if (!target) return;
        document.querySelectorAll('#nearbyAdminTabs .nav-link').forEach(function (el) {
            el.classList.toggle('active', el.getAttribute('data-bs-target') === target);
        });
        document.querySelectorAll('#nearbyAdminTabContent .tab-pane').forEach(function (el) {
            const isActive = ('#' + el.id) === target;
            el.classList.toggle('show', isActive);
            el.classList.toggle('active', isActive);
        });
        const btn = document.querySelector('[data-bs-target="' + target + '"]');
        if (btn && window.bootstrap && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(btn).show();
        }
    }

    function restoreAdminTab() {
        const hashKey = (window.location.hash || '').replace('#', '');
        const saved = sessionStorage.getItem('nearbyAdminActiveTab') || hashKey || 'overview';
        const target = Object.keys(TAB_KEYS).find(function (selector) {
            return TAB_KEYS[selector] === saved;
        });
        if (target && saved !== 'overview') {
            activateAdminTab(target);
        }
        try { sessionStorage.removeItem('nearbyAdminActiveTab'); } catch (e) {}
    }

    function reloadAdminTab(tabKey) {
        saveAdminTab(tabKey);
        window.location.assign(window.location.pathname + window.location.search + '#' + tabKey);
        window.location.reload();
    }

    document.querySelectorAll('#nearbyAdminTabs [data-bs-target]').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () {
            const target = btn.getAttribute('data-bs-target') || '';
            if (TAB_KEYS[target]) saveAdminTab(TAB_KEYS[target]);
        });
    });

    restoreAdminTab();

    function renderToolsFeedback(json, fallbackOk, fallbackErr) {
        const wrap = document.getElementById('nearbyToolsFeedbackWrap');
        const box = document.getElementById('nearbyToolsFeedback');
        const icon = document.getElementById('nearbyToolsFeedbackIcon');
        const title = document.getElementById('nearbyToolsFeedbackTitle');
        const message = document.getElementById('nearbyToolsFeedbackMessage');
        const detailsWrap = document.getElementById('nearbyToolsDetailsWrap');
        const previewWrap = document.getElementById('nearbyQualityPreviewWrap');
        const previewSummary = document.getElementById('nearbyQualityPreviewSummary');
        const previewBody = document.getElementById('nearbyQualityPreviewBody');
        if (!wrap || !title || !message) return;
        const ok = json && json.error === false;
        if (box) box.className = 'alert mb-0 py-3 shadow-sm ' + (ok ? 'alert-ok' : 'alert-danger');
        if (icon) icon.textContent = ok ? '✓' : '!';
        title.textContent = ok ? @json(__('Success')) : @json(__('Something went wrong'));
        message.textContent = (json && json.message) ? json.message : (ok ? fallbackOk : fallbackErr);
        wrap.classList.remove('d-none');
        if (toolsOutput && detailsWrap && json) {
            toolsOutput.textContent = JSON.stringify(json, null, 2);
            detailsWrap.classList.remove('d-none');
        }
        if (previewWrap && previewSummary && previewBody) {
            const preview = json && json.data && json.data.quality_preview ? json.data.quality_preview : null;
            if (preview) {
                let totalBefore = 0;
                let totalAfter = 0;
                let html = '';
                Object.keys(preview).forEach(function (slug) {
                    const cat = preview[slug] || {};
                    totalBefore += cat.before_count || 0;
                    totalAfter += cat.after_count || 0;
                    html += '<div class="mb-3"><div class="fw-semibold">' + (cat.category || slug) + ' <span class="text-muted">(' + (cat.after_count || 0) + ' / ' + (cat.before_count || 0) + ')</span></div>';
                    if ((cat.shown || []).length) {
                        html += '<div class="text-success mb-1">' + @json(__('Shown')) + ':</div><ul class="mb-2 ps-3">';
                        cat.shown.slice(0, 8).forEach(function (item) {
                            html += '<li>' + (item.name || '') + (item.quality_score ? ' <span class="text-muted">[' + item.quality_score + ']</span>' : '') + '</li>';
                        });
                        html += '</ul>';
                    }
                    if ((cat.hidden || []).length) {
                        html += '<div class="text-danger mb-1">' + @json(__('Hidden')) + ':</div><ul class="mb-0 ps-3">';
                        cat.hidden.slice(0, 8).forEach(function (item) {
                            html += '<li>' + (item.name || '') + ' <span class="text-muted">— ' + (item.reason || '') + '</span></li>';
                        });
                        html += '</ul>';
                    }
                    html += '</div>';
                });
                previewSummary.textContent = @json(__('Before filtering')) + ': ' + totalBefore + ' · ' + @json(__('After filtering')) + ': ' + totalAfter;
                previewBody.innerHTML = html;
                previewWrap.classList.remove('d-none');
            } else {
                previewWrap.classList.add('d-none');
                previewBody.innerHTML = '';
                previewSummary.textContent = '';
            }
        }
    }

    function renderSettingsFeedback(json) {
        const wrap = document.getElementById('nearbySettingsFeedbackWrap');
        const box = document.getElementById('nearbySettingsFeedback');
        if (!wrap || !box) return;
        const ok = json && json.error === false;
        box.className = 'alert mb-0 py-2 ' + (ok ? 'alert-ok' : 'alert-danger');
        box.textContent = (json && json.message) ? json.message : (ok ? @json(__('Settings saved.')) : @json(__('Could not save.')));
        wrap.classList.remove('d-none');
    }

    function renderCategoryFormFeedback(json) {
        const wrap = document.getElementById('nearbyCategoryFormFeedbackWrap');
        const box = document.getElementById('nearbyCategoryFormFeedback');
        if (!wrap || !box) return;
        const ok = json && json.error === false;
        box.className = 'alert mb-0 py-2 ' + (ok ? 'alert-ok' : 'alert-danger');
        box.textContent = (json && json.message) ? json.message : @json(__('Could not save category.'));
        wrap.classList.remove('d-none');
    }

    function renderCategoryTabFeedback(json, fallbackOk, fallbackErr) {
        const wrap = document.getElementById('nearbyCategoryFeedbackWrap');
        const box = document.getElementById('nearbyCategoryFeedback');
        if (!wrap || !box) return;
        const ok = json && json.error === false;
        box.className = 'alert mb-0 py-2 ' + (ok ? 'alert-ok' : 'alert-danger');
        box.textContent = (json && json.message) ? json.message : (ok ? fallbackOk : fallbackErr);
        wrap.classList.remove('d-none');
    }

    const categoryRoutes = {
        bulkStore: @json(route('nearby-places.categories.bulk-store')),
        bulkArchive: @json(route('nearby-places.categories.bulk-archive')),
        bulkRestore: @json(route('nearby-places.categories.bulk-restore')),
        bulkPatch: @json(route('nearby-places.categories.bulk-patch')),
        quickUpdate: @json(route('nearby-places.categories.quick-update')),
    };

    let nearbyQuickEditEnabled = false;

    function nearbyGetSelectedActiveIds() {
        return Array.from(document.querySelectorAll('.nearby-category-row-check:checked')).map(function (el) {
            return parseInt(el.value, 10);
        }).filter(function (id) { return Number.isFinite(id) && id > 0; });
    }

    function nearbyGetSelectedArchivedIds() {
        return Array.from(document.querySelectorAll('.nearby-archived-check:checked')).map(function (el) {
            return parseInt(el.value, 10);
        }).filter(function (id) { return Number.isFinite(id) && id > 0; });
    }

    function nearbyUpdateBulkBar() {
        const ids = nearbyGetSelectedActiveIds();
        const bar = document.getElementById('nearbyCategoryBulkBar');
        const countEl = document.getElementById('nearbyCategorySelectedCount');
        if (countEl) countEl.textContent = ids.length + ' ' + @json(__('selected'));
        if (bar) bar.classList.toggle('d-none', ids.length === 0 || nearbyQuickEditEnabled);
    }

    function nearbyBindCategoryChecks() {
        document.querySelectorAll('.nearby-category-row-check, .nearby-archived-check').forEach(function (el) {
            el.addEventListener('change', function () {
                const row = el.closest('tr');
                if (row) row.classList.toggle('is-selected', el.checked);
                nearbyUpdateBulkBar();
            });
        });

        document.getElementById('nearbyCategorySelectAll')?.addEventListener('change', function () {
            const checked = this.checked;
            document.querySelectorAll('.nearby-category-row:not([style*="display: none"]) .nearby-category-row-check').forEach(function (el) {
                el.checked = checked;
                const row = el.closest('tr');
                if (row) row.classList.toggle('is-selected', checked);
            });
            nearbyUpdateBulkBar();
        });

        document.getElementById('nearbyArchivedSelectAll')?.addEventListener('change', function () {
            const checked = this.checked;
            document.querySelectorAll('.nearby-archived-check').forEach(function (el) {
                el.checked = checked;
            });
        });
    }

    function nearbyParseBulkCategoryLines(text) {
        const rows = [];
        String(text || '').split(/\r?\n/).forEach(function (line) {
            line = line.trim();
            if (!line || line.startsWith('#')) return;
            const parts = line.split(/[|,]/).map(function (part) { return part.trim(); });
            if (!parts[0]) return;
            rows.push({
                name: parts[0],
                google_place_type: parts[1] || parts[0].toLowerCase().replace(/\s+/g, '_'),
                icon: parts[2] || '',
                default_radius_m: parts[3] ? parseInt(parts[3], 10) : null,
                max_results: parts[4] ? parseInt(parts[4], 10) : null,
                sort_order: parts[5] ? parseInt(parts[5], 10) : null,
                is_active: '1',
            });
        });
        return rows;
    }

    function nearbyRenderBulkPreview() {
        const preview = document.getElementById('nearbyBulkCategoryPreview');
        const rows = nearbyParseBulkCategoryLines(document.getElementById('nearbyBulkCategoryInput')?.value || '');
        if (!preview) return;
        if (!rows.length) {
            preview.textContent = @json(__('No valid lines yet.'));
            return;
        }
        preview.innerHTML = '<strong>' + rows.length + '</strong> ' + @json(__('categories ready')) + ': ' +
            rows.map(function (row) { return row.name; }).join(', ');
    }

    function nearbyNormalizeIconClass(icon) {
        icon = String(icon || '').trim();
        if (!icon) return '';
        if (/\b(fas|far|fab|fa-solid|fa-regular|fa-brands)\b/i.test(icon)) return icon;
        if (icon.indexOf('fa-') === 0) return 'fas ' + icon;
        return 'fas fa-' + icon.replace(/^-/, '');
    }

    function nearbyRenderIconCell(icon) {
        const iconClass = nearbyNormalizeIconClass(icon);
        if (!iconClass) {
            return '<span class="text-muted">—</span>';
        }
        return '<i class="' + iconClass + '" aria-hidden="true"></i>' +
            (icon ? '<span class="category-icon-label">' + icon + '</span>' : '');
    }

    function nearbySetQuickEditMode(enabled) {
        nearbyQuickEditEnabled = !!enabled;
        const table = document.getElementById('nearbyActiveCategoriesTable');
        const bar = document.getElementById('nearbyQuickEditBar');
        const toggleBtn = document.getElementById('nearbyQuickEditToggleBtn');
        const bulkBar = document.getElementById('nearbyCategoryBulkBar');
        if (bar) bar.classList.toggle('d-none', !nearbyQuickEditEnabled);
        if (toggleBtn) {
            toggleBtn.textContent = nearbyQuickEditEnabled ? @json(__('Exit quick edit')) : @json(__('Quick edit all'));
            toggleBtn.classList.toggle('btn-warning', nearbyQuickEditEnabled);
            toggleBtn.classList.toggle('btn-outline-secondary', !nearbyQuickEditEnabled);
        }
        if (bulkBar && nearbyQuickEditEnabled) bulkBar.classList.add('d-none');

        document.querySelectorAll('#nearbyActiveCategoriesTable tbody .nearby-category-row').forEach(function (row) {
            if (nearbyQuickEditEnabled) {
                nearbyEnableQuickEditRow(row);
            } else {
                nearbyDisableQuickEditRow(row);
            }
        });

        document.querySelectorAll('#nearbyActiveCategoriesTable .nearby-row-actions, #nearbyActiveCategoriesTable .nearby-category-row-check, #nearbyCategorySelectAll').forEach(function (el) {
            el.closest('td, th')?.classList.toggle('d-none', nearbyQuickEditEnabled);
        });
    }

    function nearbyBindIconPreviewInput(input) {
        if (!input || input.dataset.previewBound === '1') return;
        input.dataset.previewBound = '1';
        const preview = input.closest('.nearby-cell-icon')?.querySelector('.nearby-icon-preview');
        input.addEventListener('input', function () {
            if (preview) preview.className = 'nearby-icon-preview ' + nearbyNormalizeIconClass(input.value);
        });
    }

    function nearbyEnableQuickEditRow(row) {
        const data = JSON.parse(row.getAttribute('data-category') || '{}');
        const iconCell = row.querySelector('.nearby-cell-icon');
        if (iconCell) {
            iconCell.innerHTML =
                '<div class="text-center">' +
                '<i class="nearby-icon-preview ' + nearbyNormalizeIconClass(data.icon) + '" aria-hidden="true"></i>' +
                '<input type="text" class="form-control quick-edit-input mt-1" data-field="icon" value="' + (data.icon || '').replace(/"/g, '&quot;') + '" placeholder="fas fa-hospital">' +
                '</div>';
            nearbyBindIconPreviewInput(iconCell.querySelector('[data-field="icon"]'));
        }
        row.querySelector('.nearby-cell-name').innerHTML =
            '<input type="text" class="form-control quick-edit-input" data-field="name" value="' + (data.name || '').replace(/"/g, '&quot;') + '">';
        row.querySelector('.nearby-cell-google').innerHTML =
            '<input type="text" class="form-control quick-edit-input" data-field="google_place_type" value="' + (data.google_place_type || '').replace(/"/g, '&quot;') + '">';
        row.querySelector('.nearby-cell-radius').innerHTML =
            '<input type="number" class="form-control quick-edit-input" data-field="default_radius_m" min="100" max="50000" value="' + (data.default_radius_m || 2000) + '">';
        row.querySelector('.nearby-cell-max').innerHTML =
            '<input type="number" class="form-control quick-edit-input" data-field="max_results" min="1" max="20" value="' + (data.max_results || 5) + '">';
        row.querySelector('.nearby-cell-min-rating').innerHTML =
            '<input type="number" class="form-control quick-edit-input" data-field="min_rating" min="0" max="5" step="0.1" value="' + (data.min_rating ?? 3.5) + '">';
        row.querySelector('.nearby-cell-filter-max').innerHTML =
            '<input type="number" class="form-control quick-edit-input" data-field="max_results_after_filter" min="1" max="20" value="' + (data.max_results_after_filter || data.max_results || 5) + '">';
        row.querySelector('.nearby-cell-sort').innerHTML =
            '<input type="number" class="form-control quick-edit-input" data-field="sort_order" min="0" max="9999" value="' + (data.sort_order ?? 0) + '">';
        row.querySelector('.nearby-cell-status').innerHTML =
            '<select class="form-control quick-edit-input" data-field="is_active">' +
            '<option value="1"' + (data.is_active ? ' selected' : '') + '>' + @json(__('Active')) + '</option>' +
            '<option value="0"' + (!data.is_active ? ' selected' : '') + '>' + @json(__('Inactive')) + '</option>' +
            '</select>';
    }

    function nearbyDisableQuickEditRow(row) {
        const data = JSON.parse(row.getAttribute('data-category') || '{}');
        row.querySelector('.nearby-cell-name').innerHTML =
            '<span class="quick-view">' + (data.name || '') + '</span>';
        const iconCell = row.querySelector('.nearby-cell-icon');
        if (iconCell) iconCell.innerHTML = nearbyRenderIconCell(data.icon || '');
        row.querySelector('.nearby-cell-google').innerHTML = '<code>' + (data.google_place_type || '—') + '</code>';
        row.querySelector('.nearby-cell-radius').textContent = new Intl.NumberFormat().format(data.default_radius_m || 0) + ' m';
        row.querySelector('.nearby-cell-max').textContent = data.max_results ?? '—';
        row.querySelector('.nearby-cell-min-rating').textContent = Number(data.min_rating ?? 3.5).toFixed(1);
        row.querySelector('.nearby-cell-filter-max').textContent = data.max_results_after_filter || data.max_results || '—';
        row.querySelector('.nearby-cell-sort').textContent = data.sort_order ?? 0;
        row.querySelector('.nearby-cell-status').innerHTML = data.is_active
            ? '<span class="status-pill-active quick-view">' + @json(__('Active')) + '</span>'
            : '<span class="status-pill-inactive quick-view">' + @json(__('Inactive')) + '</span>';
    }

    function nearbyCollectQuickEditPayload() {
        const categories = [];
        document.querySelectorAll('#nearbyActiveCategoriesTable tbody .nearby-category-row').forEach(function (row) {
            const id = parseInt(row.getAttribute('data-category-id') || '0', 10);
            if (!id) return;
            const payload = { id: id };
            row.querySelectorAll('[data-field]').forEach(function (input) {
                payload[input.getAttribute('data-field')] = input.value;
            });
            categories.push(payload);
        });
        return categories;
    }

    async function nearbyPutJson(url, body) {
        const res = await fetch(url, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(body),
        });
        return res.json();
    }

    nearbyBindCategoryChecks();

    document.getElementById('nearbyBulkCategoryInput')?.addEventListener('input', nearbyRenderBulkPreview);

    document.getElementById('nearbyBulkCategorySubmitBtn')?.addEventListener('click', async function () {
        const rows = nearbyParseBulkCategoryLines(document.getElementById('nearbyBulkCategoryInput')?.value || '');
        if (!rows.length) {
            alert(@json(__('Add at least one valid category line.')));
            return;
        }
        this.disabled = true;
        try {
            const json = await postJson(categoryRoutes.bulkStore, { categories: rows });
            const feedbackWrap = document.getElementById('nearbyBulkCategoryFeedbackWrap');
            const feedback = document.getElementById('nearbyBulkCategoryFeedback');
            if (feedbackWrap && feedback) {
                feedback.className = 'alert mb-0 py-2 ' + ((json.error === false) ? 'alert-ok' : 'alert-danger');
                feedback.textContent = json.message || '';
                feedbackWrap.classList.remove('d-none');
            }
            if (!json.error) {
                bootstrap.Modal.getInstance(document.getElementById('nearbyBulkCategoryModal'))?.hide();
                reloadAdminTab('categories');
            }
        } finally {
            this.disabled = false;
        }
    });

    document.getElementById('nearbyBulkArchiveBtn')?.addEventListener('click', async function () {
        const ids = nearbyGetSelectedActiveIds();
        if (!ids.length) return;
        if (!confirm(@json(__('Archive')) + ' ' + ids.length + ' ' + @json(__('selected categories?'))) ) return;
        const json = await postJson(categoryRoutes.bulkArchive, { ids: ids });
        if (!json.error) reloadAdminTab('categories');
        else renderCategoryTabFeedback(json, '', @json(__('Bulk archive failed.')));
    });

    document.getElementById('nearbyBulkRestoreBtn')?.addEventListener('click', async function () {
        const ids = nearbyGetSelectedArchivedIds();
        if (!ids.length) {
            alert(@json(__('Select archived categories to restore.')));
            return;
        }
        const json = await postJson(categoryRoutes.bulkRestore, { ids: ids });
        if (!json.error) reloadAdminTab('categories');
        else renderCategoryTabFeedback(json, '', @json(__('Bulk restore failed.')));
    });

    document.getElementById('nearbyBulkEditSelectedBtn')?.addEventListener('click', function () {
        const ids = nearbyGetSelectedActiveIds();
        if (!ids.length) return;
        const summary = document.getElementById('nearbyBulkEditSummary');
        if (summary) summary.textContent = @json(__('Apply field changes to')) + ' ' + ids.length + ' ' + @json(__('selected categories.'));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('nearbyBulkEditModal')).show();
    });

    document.getElementById('nearbyBulkEditSubmitBtn')?.addEventListener('click', async function () {
        const ids = nearbyGetSelectedActiveIds();
        if (!ids.length) return;
        const fields = {};
        const radius = document.getElementById('nearbyBulkEditRadius')?.value;
        const max = document.getElementById('nearbyBulkEditMax')?.value;
        const minRating = document.getElementById('nearbyBulkEditMinRating')?.value;
        const filterMax = document.getElementById('nearbyBulkEditFilterMax')?.value;
        const sort = document.getElementById('nearbyBulkEditSort')?.value;
        const status = document.getElementById('nearbyBulkEditStatus')?.value;
        const cacheValue = document.getElementById('nearbyBulkEditCacheValue')?.value;
        const cacheUnit = document.getElementById('nearbyBulkEditCacheUnit')?.value || 'days';
        if (radius !== '') fields.default_radius_m = parseInt(radius, 10);
        if (max !== '') fields.max_results = parseInt(max, 10);
        if (minRating !== '') fields.min_rating = parseFloat(minRating);
        if (filterMax !== '') fields.max_results_after_filter = parseInt(filterMax, 10);
        if (sort !== '') fields.sort_order = parseInt(sort, 10);
        if (status !== '') fields.is_active = status;
        if (cacheValue !== '') {
            fields.cache_ttl_value = parseInt(cacheValue, 10);
            fields.cache_ttl_unit = cacheUnit;
        }
        if (Object.keys(fields).length === 0) {
            alert(@json(__('Fill at least one field to update.')));
            return;
        }
        this.disabled = true;
        try {
            const json = await postJson(categoryRoutes.bulkPatch, { ids: ids, fields: fields });
            if (!json.error) {
                bootstrap.Modal.getInstance(document.getElementById('nearbyBulkEditModal'))?.hide();
                reloadAdminTab('categories');
            } else {
                renderCategoryTabFeedback(json, '', @json(__('Bulk edit failed.')));
            }
        } finally {
            this.disabled = false;
        }
    });

    document.getElementById('nearbyQuickEditToggleBtn')?.addEventListener('click', function () {
        nearbySetQuickEditMode(!nearbyQuickEditEnabled);
    });

    document.getElementById('nearbyQuickEditCancelBtn')?.addEventListener('click', function () {
        nearbySetQuickEditMode(false);
    });

    document.getElementById('nearbyQuickEditSaveBtn')?.addEventListener('click', async function () {
        const categories = nearbyCollectQuickEditPayload();
        if (!categories.length) return;
        this.disabled = true;
        try {
            const json = await nearbyPutJson(categoryRoutes.quickUpdate, { categories: categories });
            if (!json.error) {
                nearbySetQuickEditMode(false);
                reloadAdminTab('categories');
            } else {
                renderCategoryTabFeedback(json, '', @json(__('Quick edit save failed.')));
            }
        } finally {
            this.disabled = false;
        }
    });

    function setToolsBusy(isBusy) {
        ['nearbyRefreshPropertyBtn', 'nearbyRefreshAllCachedBtn', 'nearbyClearPropertyBtn', 'nearbyClearAllBtn'].forEach(function (id) {
            const btn = document.getElementById(id);
            if (btn) btn.disabled = isBusy;
        });
        if (toolsSpinner) toolsSpinner.classList.toggle('d-none', !isBusy);
    }

    async function postJson(url, body) {
        body = body || {};
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(body),
        });
        return res.json();
    }

    function readPropertyId(inputId) {
        const el = document.getElementById(inputId);
        if (!el) return null;
        const raw = String(el.value ?? '').trim();
        if (!raw) return null;
        const id = Number.parseInt(raw, 10);
        return Number.isFinite(id) && id >= 1 ? id : null;
    }

    function readCachePropertyTarget() {
        const select = document.getElementById('nearbyCachePropertySelect');
        const selectVal = String(select?.value ?? '').trim();
        if (selectVal === 'all') {
            return { mode: 'all' };
        }
        if (selectVal) {
            const selectedId = Number.parseInt(selectVal, 10);
            if (Number.isFinite(selectedId) && selectedId >= 1) {
                return { mode: 'single', property_id: selectedId };
            }
        }
        const typedId = readPropertyId('nearbyPropertyId');
        if (typedId) {
            return { mode: 'single', property_id: typedId };
        }
        return { mode: 'none' };
    }

    function readReviewPropertyTarget() {
        const select = document.getElementById('nearbyReviewPropertySelect');
        const selectVal = String(select?.value ?? '').trim();
        if (selectVal === 'all') {
            return { mode: 'all' };
        }
        if (selectVal) {
            const selectedId = Number.parseInt(selectVal, 10);
            if (Number.isFinite(selectedId) && selectedId >= 1) {
                return { mode: 'single', property_id: selectedId };
            }
        }
        const typedId = readPropertyId('nearbyReviewPropertyId');
        if (typedId) {
            return { mode: 'single', property_id: typedId };
        }
        return { mode: 'none' };
    }

    document.getElementById('nearbyReviewPropertySelect')?.addEventListener('change', function () {
        const input = document.getElementById('nearbyReviewPropertyId');
        if (!input) return;
        if (this.value && this.value !== 'all') {
            input.value = this.value;
        }
    });

    document.getElementById('nearbyReviewPropertyId')?.addEventListener('input', function () {
        const select = document.getElementById('nearbyReviewPropertySelect');
        if (select && select.value !== 'all') {
            select.value = '';
        }
    });

    document.getElementById('nearbyCachePropertySelect')?.addEventListener('change', function () {
        const input = document.getElementById('nearbyPropertyId');
        if (!input) return;
        if (this.value && this.value !== 'all') {
            input.value = this.value;
        }
    });

    document.getElementById('nearbyPropertyId')?.addEventListener('input', function () {
        const select = document.getElementById('nearbyCachePropertySelect');
        if (select && select.value !== 'all') {
            select.value = '';
        }
    });

    function readCacheCategoryId() {
        const raw = String(document.getElementById('nearbyCacheCategoryId')?.value ?? '').trim();
        if (!raw) return null;
        const id = parseInt(raw, 10);
        return Number.isFinite(id) && id >= 1 ? id : null;
    }

    function fillCategoryCacheTtlFields(hours) {
        const valueInput = document.getElementById('nearbyCategoryCacheTtlValue');
        const unitSelect = document.getElementById('nearbyCategoryCacheTtlUnit');
        if (!valueInput || !unitSelect) return;

        if (hours === null || hours === undefined || hours === '') {
            valueInput.value = '';
            unitSelect.value = 'days';
            return;
        }

        const totalHours = parseInt(hours, 10);
        if (!Number.isFinite(totalHours) || totalHours <= 0) {
            valueInput.value = '';
            unitSelect.value = 'days';
            return;
        }

        if (totalHours % 24 === 0) {
            valueInput.value = String(totalHours / 24);
            unitSelect.value = 'days';
        } else {
            valueInput.value = String(totalHours);
            unitSelect.value = 'hours';
        }
    }

    async function runCacheToolAction(fallbackOk, fallbackErr, action) {
        setToolsBusy(true);
        try {
            let json;
            try { json = await action(); } catch (e) { json = { error: true, message: @json(__('Network error.')) }; }
            renderToolsFeedback(json, fallbackOk, fallbackErr);
            if (!json.error) reloadAdminTab('cache');
            return json;
        } finally { setToolsBusy(false); }
    }

    document.getElementById('nearbyCheckApiKeyBtn')?.addEventListener('click', async function () {
        const status = document.getElementById('nearbyApiKeyStatus');
        if (status) { status.textContent = @json(__('Checking…')); status.className = 'ms-2 small text-muted'; }
        const json = await fetch(@json(route('nearby-places.api-key.check'))).then(function (r) { return r.json(); });
        if (status) {
            status.textContent = json.message || '';
            status.className = 'ms-2 small fw-semibold ' + (json.error ? 'text-danger' : 'text-dark');
        }
    });

    document.getElementById('nearbyPreviewApiBtn')?.addEventListener('click', function () {
        const id = readPropertyId('nearbyPreviewPropertyId');
        if (!id) { alert(@json(__('Enter a valid Property ID.'))); return; }
        window.open(apiBase + '/api/nearby-places/property/' + id, '_blank');
    });

    document.getElementById('nearbyCategorySearch')?.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.nearby-category-row').forEach(function (row) {
            const hay = row.getAttribute('data-search') || '';
            row.style.display = (!q || hay.indexOf(q) !== -1) ? '' : 'none';
        });
        nearbyUpdateBulkBar();
    });

    document.getElementById('nearbySettingsForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('nearbySettingsSaveBtn');
        const spin = document.getElementById('nearbySettingsSaveSpinner');
        if (btn) btn.disabled = true;
        if (spin) spin.classList.remove('d-none');
        try {
            const res = await fetch(@json(route('nearby-places.settings.update')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(Object.fromEntries(new FormData(this).entries())),
            });
            let json = await res.json().catch(function () { return { error: true, message: @json(__('Unexpected response')) }; });
            if (!res.ok && json.error !== true) json = { error: true, message: json.message || @json(__('Request failed')) };
            renderSettingsFeedback(json);
            if (!json.error) saveAdminTab('settings');
        } finally {
            if (btn) btn.disabled = false;
            if (spin) spin.classList.add('d-none');
        }
    });

    document.querySelectorAll('.nearby-edit-category').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const c = JSON.parse(this.dataset.category);
            document.getElementById('nearbyCategoryModalTitle').textContent = @json(__('Edit category'));
            document.getElementById('nearbyCategoryId').value = c.id;
            categoryForm.name.value = c.name;
            categoryForm.slug.value = c.slug;
            categoryForm.google_place_type.value = c.google_place_type || '';
            categoryForm.icon.value = c.icon || '';
            categoryForm.default_radius_m.value = c.default_radius_m;
            categoryForm.max_results.value = c.max_results;
            categoryForm.sort_order.value = c.sort_order;
            categoryForm.is_active.value = c.is_active ? '1' : '0';
            categoryForm.min_rating.value = c.min_rating ?? 3.5;
            categoryForm.min_reviews.value = c.min_reviews ?? 3;
            categoryForm.allow_unrated.value = c.allow_unrated ? '1' : '0';
            categoryForm.hide_generic_places.value = (c.hide_generic_places ?? true) ? '1' : '0';
            categoryForm.hide_suspicious_same_location.value = (c.hide_suspicious_same_location ?? true) ? '1' : '0';
            categoryForm.max_distance_m.value = c.max_distance_m ?? '';
            categoryForm.max_results_after_filter.value = c.max_results_after_filter ?? '';
            fillCategoryCacheTtlFields(c.cache_ttl_hours ?? '');
            document.getElementById('nearbyCategoryFormFeedbackWrap')?.classList.add('d-none');
            categoryModal?.show();
        });
    });

    document.getElementById('nearbyAddCategoryBtn')?.addEventListener('click', function () {
        document.getElementById('nearbyCategoryModalTitle').textContent = @json(__('Add category'));
        categoryForm.reset();
        document.getElementById('nearbyCategoryId').value = '';
        fillCategoryCacheTtlFields('');
        document.getElementById('nearbyCategoryFormFeedbackWrap')?.classList.add('d-none');
    });

    categoryForm?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const body = Object.fromEntries(new FormData(this).entries());
        const id = body.category_id;
        delete body.category_id;
        let url = @json(route('nearby-places.categories.store'));
        let method = 'POST';
        if (id) { url = '/nearby-places/categories/' + id; method = 'PUT'; }
        const saveBtn = document.getElementById('nearbyCategorySaveBtn');
        if (saveBtn) saveBtn.disabled = true;
        try {
            const res = await fetch(url, {
                method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                body: JSON.stringify(body),
            });
            const json = await res.json();
            if (!json.error) {
                categoryModal?.hide();
                reloadAdminTab('categories');
            } else {
                renderCategoryFormFeedback(json);
            }
        } finally { if (saveBtn) saveBtn.disabled = false; }
    });

    document.querySelectorAll('.nearby-delete-category').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const name = this.dataset.name || '';
            if (!confirm(@json(__('Archive category')) + ' "' + name + '"?')) return;
            const res = await fetch('/nearby-places/categories/' + this.dataset.id, {
                method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const json = await res.json();
            if (!json.error) reloadAdminTab('categories');
            else renderToolsFeedback(json, '', @json(__('Could not archive.')));
        });
    });

    document.querySelectorAll('.nearby-restore-category').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const name = this.dataset.name || '';
            if (!confirm(@json(__('Restore category')) + ' "' + name + '"?')) return;
            const json = await postJson('/nearby-places/categories/' + this.dataset.id + '/restore');
            if (!json.error) reloadAdminTab('categories');
            else renderToolsFeedback(json, '', @json(__('Could not restore.')));
        });
    });

    function ensureCacheTabVisible() {
        bootstrap.Tab.getOrCreateInstance(document.querySelector('[data-bs-target="#nearbyTabCache"]')).show();
    }

    document.getElementById('nearbyRefreshPropertyBtn')?.addEventListener('click', async function () {
        const target = readCachePropertyTarget();
        if (target.mode === 'none') {
            renderToolsFeedback({ error: true, message: @json(__('Choose a cached property, type a Property ID, or select all cached properties.')) }, '', '');
            ensureCacheTabVisible();
            return;
        }
        if (target.mode === 'all') {
            await runCacheToolAction(@json(__('All cached properties refreshed.')), @json(__('Refresh failed.')), function () {
                const payload = {};
                const categoryId = readCacheCategoryId();
                if (categoryId) payload.category_id = categoryId;
                return postJson(@json(route('nearby-places.refresh-all-cached')), payload);
            });
            return;
        }
        await runCacheToolAction(@json(__('Refreshed successfully.')), @json(__('Refresh failed.')), function () {
            const payload = { property_id: target.property_id };
            const categoryId = readCacheCategoryId();
            if (categoryId) payload.category_id = categoryId;
            return postJson(@json(route('nearby-places.refresh-property')), payload);
        });
    });

    document.getElementById('nearbyRefreshAllCachedBtn')?.addEventListener('click', async function () {
        await runCacheToolAction(@json(__('All cached properties refreshed.')), @json(__('Refresh failed.')), function () {
            const payload = {};
            const categoryId = readCacheCategoryId();
            if (categoryId) payload.category_id = categoryId;
            return postJson(@json(route('nearby-places.refresh-all-cached')), payload);
        });
    });

    document.getElementById('nearbyClearPropertyBtn')?.addEventListener('click', async function () {
        const target = readCachePropertyTarget();
        if (target.mode === 'none') {
            renderToolsFeedback({ error: true, message: @json(__('Choose a cached property or type a Property ID.')) }, '', '');
            ensureCacheTabVisible();
            return;
        }
        if (target.mode === 'all') {
            if (!confirm(@json(__('Clear ALL nearby and travel-time cache for every property?'))) ) return;
            await runCacheToolAction(@json(__('All cache cleared.')), @json(__('Clear failed.')), function () {
                return postJson(@json(route('nearby-places.clear-all-cache')));
            });
            return;
        }
        await runCacheToolAction(@json(__('Property cache cleared.')), @json(__('Clear failed.')), function () {
            const payload = { property_id: target.property_id };
            const categoryId = readCacheCategoryId();
            if (categoryId) payload.category_id = categoryId;
            return postJson(@json(route('nearby-places.clear-property-cache')), payload);
        });
    });

    document.getElementById('nearbyClearAllBtn')?.addEventListener('click', async function () {
        if (!confirm(@json(__('Clear ALL nearby and travel-time cache for every property?'))) ) return;
        await runCacheToolAction(@json(__('All cache cleared.')), @json(__('Clear failed.')), function () {
            return postJson(@json(route('nearby-places.clear-all-cache')));
        });
    });

    const reviewSavedPropertyId = sessionStorage.getItem('nearbyReviewPropertyId');
    const reviewSavedScope = sessionStorage.getItem('nearbyReviewScope');
    if (reviewSavedScope === 'all' && document.getElementById('nearbyReviewPropertySelect')) {
        document.getElementById('nearbyReviewPropertySelect').value = 'all';
    } else if (reviewSavedPropertyId) {
        if (document.getElementById('nearbyReviewPropertyId')) {
            document.getElementById('nearbyReviewPropertyId').value = reviewSavedPropertyId;
        }
        const reviewSelect = document.getElementById('nearbyReviewPropertySelect');
        if (reviewSelect && reviewSelect.querySelector('option[value="' + reviewSavedPropertyId + '"]')) {
            reviewSelect.value = reviewSavedPropertyId;
        }
    }

    const reviewRoutes = {
        load: @json(route('nearby-places.review-places')),
        quick: @json(route('nearby-places.overrides.quick')),
    };

    async function nearbyQuickOverride(body) {
        return postJson(reviewRoutes.quick, body);
    }

    let nearbyReviewScope = 'single';

    function nearbySetReviewScope(scope) {
        nearbyReviewScope = scope === 'all' ? 'all' : 'single';
        const propertyCol = document.getElementById('nearbyReviewPropertyCol');
        if (propertyCol) {
            propertyCol.classList.toggle('d-none', nearbyReviewScope !== 'all');
        }
    }

    function nearbyRenderReviewRows(items, scope) {
        const tbody = document.getElementById('nearbyReviewTableBody');
        if (!tbody) return;
        const colSpan = scope === 'all' ? 9 : 8;
        if (!items.length) {
            tbody.innerHTML = '<tr><td colspan="' + colSpan + '" class="text-center text-muted py-4">' + @json(__('No places match this filter.')) + '</td></tr>';
            return;
        }
        tbody.innerHTML = items.map(function (item) {
            const rating = item.rating ? Number(item.rating).toFixed(1) : '—';
            const reviews = item.review_count ?? '—';
            const statusBadge = item.final_visible
                ? '<span class="review-badge-visible">' + (item.visibility_status || 'visible') + '</span>'
                : '<span class="review-badge-hidden">' + (item.visibility_status || 'hidden') + '</span>';
            const actions = [
                '<button type="button" class="btn btn-sm btn-outline-success nearby-review-action" data-action="force_show" data-item=\'' + JSON.stringify(item).replace(/'/g, '&#39;') + '\'>Show</button>',
                '<button type="button" class="btn btn-sm btn-outline-danger nearby-review-action" data-action="force_hide" data-item=\'' + JSON.stringify(item).replace(/'/g, '&#39;') + '\'>Hide</button>',
                '<button type="button" class="btn btn-sm btn-outline-primary nearby-review-action" data-action="trust" data-item=\'' + JSON.stringify(item).replace(/'/g, '&#39;') + '\'>Trust</button>',
                '<button type="button" class="btn btn-sm btn-outline-warning nearby-review-action" data-action="blacklist" data-item=\'' + JSON.stringify(item).replace(/'/g, '&#39;') + '\'>Blacklist</button>',
            ].join(' ');
            const propertyCell = scope === 'all'
                ? '<td><div class="fw-semibold small">#' + (item.property_id || '—') + '</div><div class="small text-muted">' + (item.property_title || '') + '</div></td>'
                : '';
            return '<tr>' +
                propertyCell +
                '<td><div class="fw-semibold">' + (item.name || '') + '</div><code class="small">' + (item.google_place_id || '') + '</code></td>' +
                '<td>' + (item.category_name || '') + '</td>' +
                '<td>' + statusBadge + '</td>' +
                '<td>' + (item.quality_score ?? '—') + '</td>' +
                '<td>' + (item.distance_text || '—') + '</td>' +
                '<td>' + rating + ' / ' + reviews + '</td>' +
                '<td class="small text-muted">' + (item.hidden_reason || '—') + '</td>' +
                '<td class="text-end text-nowrap">' + actions + '</td>' +
                '</tr>';
        }).join('');
    }

    async function nearbyLoadReview() {
        const target = readReviewPropertyTarget();
        if (target.mode === 'none') {
            alert(@json(__('Choose a cached property, type a Property ID, or select all cached properties.')));
            return;
        }
        saveAdminTab('review');
        try {
            sessionStorage.setItem('nearbyReviewScope', target.mode);
            if (target.mode === 'single') {
                sessionStorage.setItem('nearbyReviewPropertyId', String(target.property_id));
            }
        } catch (e) {}
        const categoryId = document.getElementById('nearbyReviewCategoryId')?.value || '';
        const status = document.getElementById('nearbyReviewStatus')?.value || 'all';
        const q = document.getElementById('nearbyReviewSearch')?.value || '';
        const payload = {
            scope: target.mode,
            category_id: categoryId ? parseInt(categoryId, 10) : null,
            status: status,
            q: q,
        };
        if (target.mode === 'single') {
            payload.property_id = target.property_id;
        }
        const json = await postJson(reviewRoutes.load, payload);
        if (json.error) {
            alert(json.message || @json(__('Could not load review.')));
            return;
        }
        const data = json.data || {};
        nearbySetReviewScope(data.scope || target.mode);
        const summary = document.getElementById('nearbyReviewSummary');
        if (summary) {
            if ((data.scope || target.mode) === 'all') {
                summary.textContent = @json(__('All cached properties')) + ': ' + (data.property_count || 0) +
                    ' · ' + @json(__('Visible')) + ': ' + ((data.totals && data.totals.visible) || 0) +
                    ' · ' + @json(__('Hidden')) + ': ' + ((data.totals && data.totals.hidden) || 0);
            } else {
                summary.textContent = (data.property_title || ('Property #' + (data.property_id || target.property_id))) +
                    ' · ' + @json(__('Visible')) + ': ' + ((data.totals && data.totals.visible) || 0) +
                    ' · ' + @json(__('Hidden')) + ': ' + ((data.totals && data.totals.hidden) || 0);
            }
        }
        nearbyRenderReviewRows(data.items || [], data.scope || target.mode);
    }

    document.getElementById('nearbyReviewLoadBtn')?.addEventListener('click', nearbyLoadReview);

    document.getElementById('nearbyReviewTableBody')?.addEventListener('click', async function (event) {
        const btn = event.target.closest('.nearby-review-action');
        if (!btn) return;
        const item = JSON.parse(btn.getAttribute('data-item'));
        const action = btn.getAttribute('data-action');
        const propertyId = item.property_id || readPropertyId('nearbyReviewPropertyId');
        if (!propertyId && action !== 'blacklist') return;
        const body = {
            action: action,
            property_id: propertyId,
            category_id: item.category_id || null,
            google_place_id: item.google_place_id || null,
            name: item.name || null,
        };
        if (body.action === 'blacklist') {
            body.property_id = null;
            body.category_id = null;
        }
        const json = await nearbyQuickOverride(body);
        if (!json.error) {
            saveAdminTab('review');
            document.getElementById('nearbyReviewLoadBtn')?.click();
        } else {
            alert(json.message || @json(__('Override failed.')));
        }
    });
});
</script>
@endsection
