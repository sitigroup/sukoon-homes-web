@extends('layouts.main')

@section('title')
    Trust Verification Packages
@endsection

@section('content')
    <section class="section tv-admin tv-admin-page">
        @include('trust-verification::admin.partials.tv-admin-theme')
        @include('trust-verification::admin.partials.nav')

        <header class="tv-admin-header">
            <div>
                <h1 class="tv-admin-header__title">Packages</h1>
                <p class="tv-admin-header__meta mb-0">Active packages appear on the web wizard.</p>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="tv-admin-header__meta">{{ $packages->count() }} shown</span>
                <a href="{{ route('trust-verification.packages.create') }}" class="btn tv-admin-btn-primary">
                    <i class="bi bi-plus-lg"></i> Add package
                </a>
            </div>
        </header>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="get" class="tv-admin-filter row g-2">
            <div class="col-md-3">
                <label class="form-label small mb-0 text-muted">Search</label>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Name or slug"
                       value="{{ $filters['q'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0 text-muted">City</label>
                <select name="city_slug" class="form-select form-select-sm">
                    <option value="">All cities</option>
                    @foreach($cityOptions as $slug => $label)
                        <option value="{{ $slug }}" @selected(($filters['city_slug'] ?? '') === $slug)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0 text-muted">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All types</option>
                    <option value="tenant" @selected(($filters['type'] ?? '') === 'tenant')>Tenant</option>
                    <option value="owner" @selected(($filters['type'] ?? '') === 'owner')>Owner</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0 text-muted">Status</label>
                <select name="is_active" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="1" @selected(($filters['is_active'] ?? '') === '1')>Active</option>
                    <option value="0" @selected(($filters['is_active'] ?? '') === '0')>Inactive</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn tv-admin-btn-primary flex-grow-1">Apply</button>
                <a href="{{ route('trust-verification.packages.index') }}" class="btn tv-admin-btn-secondary">Reset</a>
            </div>
        </form>

        @if($packages->isNotEmpty())
            <div class="tv-admin-grid tv-admin-grid--packages mb-3">
                @foreach($packages as $package)
                    @php
                        $included = collect($package->features ?? [])->where('included', true)->count();
                        $total = count($package->features ?? []);
                    @endphp
                    <article class="tv-package-card">
                        <h3 class="tv-package-card__title">{{ $package->name }}</h3>
                        <p class="tv-package-card__meta mb-0">
                            {{ $cityCatalog[$package->city_slug] ?? $package->city_slug }}
                            · {{ ucfirst($package->type) }}
                            · <span class="tv-price-badge">₹{{ number_format($package->price) }}</span>
                        </p>
                        <p class="tv-package-card__meta mb-0">
                            SLA {{ $package->delivery_hours }}h · {{ $included }}/{{ $total }} checks · {{ $package->orders_count }} orders
                        </p>
                        <div class="tv-package-card__footer">
                            @if($package->is_active)
                                <span class="tv-admin-chip-success">Active</span>
                            @else
                                <span class="tv-admin-chip-muted">Inactive</span>
                            @endif
                            <a href="{{ route('trust-verification.packages.edit', $package) }}" class="btn btn-sm tv-admin-btn-secondary ms-auto">Edit</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        <div class="tv-admin-card">
            <div class="tv-admin-card__header">
                <strong class="tv-page-header__title">All packages</strong>
                <span class="tv-admin-header__meta">Full table view</span>
            </div>
            <div class="tv-admin-card__body tv-admin-card__body--flush">
                <div class="table-responsive">
                    <table class="table table-striped align-middle tv-admin-table mb-0">
                        <thead>
                        <tr>
                            <th>Package</th>
                            <th>City</th>
                            <th>Type</th>
                            <th>Price</th>
                            <th>Delivery</th>
                            <th>Checks</th>
                            <th>Orders</th>
                            <th>Status</th>
                            <th>Sort</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($packages as $package)
                            @php
                                $included = collect($package->features ?? [])->where('included', true)->count();
                                $total = count($package->features ?? []);
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $package->name }}</strong><br>
                                    <code class="small text-muted">{{ $package->slug }}</code>
                                </td>
                                <td>{{ $cityCatalog[$package->city_slug] ?? $package->city_slug }}</td>
                                <td>{{ ucfirst($package->type) }}</td>
                                <td><span class="tv-price-badge">₹{{ number_format($package->price) }}</span></td>
                                <td>{{ $package->delivery_hours }}h</td>
                                <td>{{ $included }}/{{ $total }}</td>
                                <td>{{ $package->orders_count }}</td>
                                <td>
                                    @if($package->is_active)
                                        <span class="tv-admin-chip-success">Active</span>
                                    @else
                                        <span class="tv-admin-chip-muted">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $package->sort_order }}</td>
                                <td>
                                    <a href="{{ route('trust-verification.packages.edit', $package) }}" class="btn btn-sm tv-admin-btn-secondary">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10">
                                <div class="tv-admin-empty-state">
                                    <div class="tv-admin-empty-state__icon"><i class="bi bi-box-seam"></i></div>
                                    @if(($filters['q'] ?? '') !== '' || ($filters['city_slug'] ?? '') !== '' || ($filters['type'] ?? '') !== '' || ($filters['is_active'] ?? '') !== '')
                                        <p class="mb-0">No packages match these filters.</p>
                                    @else
                                        <p class="mb-0">No packages yet — run seeder or create one.</p>
                                    @endif
                                </div>
                            </td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
