@extends('layouts.main')

@section('title')
    Trust Verification Cities
@endsection

@section('content')
    <section class="section tv-admin tv-admin-page">
        @include('trust-verification::admin.partials.tv-admin-theme')
        @include('trust-verification::admin.partials.nav')

        <header class="tv-admin-header">
            <div>
                <h1 class="tv-admin-header__title">Cities</h1>
                <p class="tv-admin-header__meta mb-0">Only enabled cities appear on the web/API.</p>
            </div>
            <span class="tv-admin-header__meta">{{ $cities->count() }} shown</span>
        </header>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="tv-help-block mb-3">
                    Add packages for a city before enabling it on the web wizard.
                </div>

                <form method="get" class="tv-admin-filter row g-2">
                    <div class="col-md-5">
                        <label class="form-label small mb-0 text-muted">Search</label>
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Name or slug"
                               value="{{ $filters['q'] ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0 text-muted">Status</label>
                        <select name="is_enabled" class="form-select form-select-sm">
                            <option value="">All</option>
                            <option value="1" @selected(($filters['is_enabled'] ?? '') === '1')>Enabled</option>
                            <option value="0" @selected(($filters['is_enabled'] ?? '') === '0')>Disabled</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end gap-2">
                        <button type="submit" class="btn tv-admin-btn-primary flex-grow-1">Apply</button>
                        <a href="{{ route('trust-verification.cities.index') }}" class="btn tv-admin-btn-secondary">Reset</a>
                    </div>
                </form>

                @forelse($cities as $city)
                    <div class="tv-admin-list-card">
                        <div class="flex-grow-1">
                            <h3 class="tv-admin-list-card__title">{{ $city->label }}</h3>
                            <form method="post" action="{{ route('trust-verification.cities.update', $city) }}" class="d-flex gap-2 align-items-center flex-wrap mb-2">
                                @csrf
                                @method('PUT')
                                <input type="text" name="label" class="form-control" style="max-width: 16rem;" value="{{ $city->label }}" required>
                                <input type="hidden" name="sort_order" value="{{ $city->sort_order }}">
                                <input type="hidden" name="is_enabled" value="{{ $city->is_enabled ? '1' : '0' }}">
                                <button type="submit" class="btn btn-sm tv-admin-btn-secondary">Save name</button>
                            </form>
                            <p class="tv-admin-list-card__meta mb-0">
                                <code>{{ $city->slug }}</code>
                                · Sort {{ $city->sort_order }}
                            </p>
                        </div>
                        <div class="tv-admin-list-card__actions">
                            <span class="tv-chip-info">{{ $city->packages_count }} packages</span>
                            @if($city->is_enabled)
                                <span class="tv-admin-chip-success">Enabled</span>
                            @else
                                <span class="tv-admin-chip-muted">Disabled</span>
                            @endif
                            <form method="post" action="{{ route('trust-verification.cities.toggle', $city) }}" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm tv-admin-btn-secondary">
                                    {{ $city->is_enabled ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="tv-admin-empty-state">
                    <div class="tv-admin-empty-state__icon"><i class="bi bi-geo-alt"></i></div>
                        @if(($filters['q'] ?? '') !== '' || ($filters['is_enabled'] ?? '') !== '')
                            <p class="mb-0">No cities match these filters.</p>
                        @else
                            <p class="mb-0">No cities — migration may be pending.</p>
                        @endif
                    </div>
                @endforelse
            </div>

            <div class="col-lg-5">
                <div class="tv-admin-card">
                    <div class="tv-admin-card__header">
                        <strong class="tv-page-header__title">Add city</strong>
                    </div>
                    <div class="tv-admin-card__body">
                        <form method="post" action="{{ route('trust-verification.cities.store') }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">Display name</label>
                                <input type="text" name="label" class="form-control" placeholder="Jodhpur" required maxlength="120">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Slug (optional)</label>
                                <input type="text" name="slug" class="form-control" placeholder="jodhpur" maxlength="80">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Sort order</label>
                                <input type="number" name="sort_order" class="form-control" value="10" min="0" max="999">
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" name="is_enabled" value="1" class="form-check-input" id="city_enabled">
                                <label class="form-check-label" for="city_enabled">Enable on web immediately</label>
                            </div>
                            <button type="submit" class="btn tv-admin-btn-primary">Add city</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
