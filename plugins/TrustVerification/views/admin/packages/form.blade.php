@extends('layouts.main')

@section('title')
    {{ $isEdit ? 'Edit' : 'Create' }} Trust Verification Package
@endsection

@section('content')
    <section class="section tv-admin tv-admin-page">
        @include('trust-verification::admin.partials.tv-admin-theme')
        @include('trust-verification::admin.partials.nav')

        <a href="{{ route('trust-verification.packages.index') }}" class="tv-link-back">
            <i class="bi bi-arrow-left"></i> All packages
        </a>

        <div class="tv-admin-card">
            <div class="tv-admin-card__header">
                <strong class="tv-page-header__title">{{ $isEdit ? 'Edit package' : 'New package' }}</strong>
            </div>
            <div class="tv-admin-card__body">
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form method="post" action="{{ $isEdit ? route('trust-verification.packages.update', $package) : route('trust-verification.packages.store') }}">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Verification type</label>
                            <select name="type" class="form-select" required>
                                <option value="tenant" @selected(old('type', $package->type) === 'tenant')>Tenant (landlord orders)</option>
                                <option value="owner" @selected(old('type', $package->type) === 'owner')>Owner (renter orders)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <select name="city_slug" class="form-select" required>
                                @foreach($cityCatalog as $slug => $label)
                                    <option value="{{ $slug }}" @selected(old('city_slug', $package->city_slug) === $slug)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sort order</label>
                            <input type="number" name="sort_order" class="form-control" min="0" max="999"
                                   value="{{ old('sort_order', $package->sort_order ?? 0) }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Display name</label>
                            <input type="text" name="name" class="form-control" maxlength="120" required
                                   value="{{ old('name', $package->name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Slug (optional)</label>
                            <input type="text" name="slug" class="form-control" maxlength="120"
                                   value="{{ old('slug', $package->slug) }}"
                                   placeholder="Auto-generated if blank">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Price (INR)</label>
                            <input type="number" name="price" class="form-control" min="1" max="999999" required
                                   value="{{ old('price', $package->price) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Delivery SLA (hours)</label>
                            <input type="number" name="delivery_hours" class="form-control" min="1" max="720" required
                                   value="{{ old('delivery_hours', $package->delivery_hours ?? 72) }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                                       @checked(old('is_active', $package->is_active ?? true))>
                                <label class="form-check-label" for="is_active">Active (visible on web)</label>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <p class="tv-admin-section-title mb-1">Included checks</p>
                    <p class="text-muted small mb-3">Toggle which background checks this package includes.</p>

                    <div class="row g-2 mb-3">
                        @foreach($features as $feature)
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input"
                                           name="features_included[{{ $feature['key'] }}]" value="1" id="feat_{{ $feature['key'] }}"
                                           @checked(old('features_included.'.$feature['key'], $feature['included']))>
                                    <label class="form-check-label" for="feat_{{ $feature['key'] }}">{{ $feature['label'] }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button type="submit" class="btn tv-admin-btn-primary">{{ $isEdit ? 'Save changes' : 'Create package' }}</button>
                </form>

                @if($isEdit && $package->priceLogs->isNotEmpty())
                    <hr>
                    <p class="tv-admin-section-title mb-2">Price history</p>
                    <div class="table-responsive">
                        <table class="table table-sm tv-admin-table mb-0">
                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>Old</th>
                                <th>New</th>
                                <th>By admin</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($package->priceLogs as $log)
                                <tr>
                                    <td>{{ $log->created_at?->format('d M Y H:i') }}</td>
                                    <td><span class="tv-price-badge">₹{{ number_format($log->old_price) }}</span></td>
                                    <td><span class="tv-price-badge">₹{{ number_format($log->new_price) }}</span></td>
                                    <td>{{ $log->changed_by ? '#'.$log->changed_by : '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
