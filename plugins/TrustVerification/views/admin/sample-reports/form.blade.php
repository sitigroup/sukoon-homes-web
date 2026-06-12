@extends('layouts.main')

@section('title')
    {{ $isEdit ? 'Edit' : 'Add' }} Sample Report
@endsection

@section('content')
    <section class="section tv-admin tv-admin-page">
        @include('trust-verification::admin.partials.tv-admin-theme')
        @include('trust-verification::admin.partials.nav')

        <a href="{{ route('trust-verification.sample-reports.index') }}" class="tv-link-back">
            <i class="bi bi-arrow-left"></i> Sample Reports
        </a>

        <div class="tv-admin-card">
            <div class="tv-admin-card__header">
                <strong class="tv-page-header__title">{{ $isEdit ? 'Edit sample report' : 'New sample report' }}</strong>
            </div>
            <div class="tv-admin-card__body">
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form method="post" action="{{ $isEdit ? route('trust-verification.sample-reports.update', $sample) : route('trust-verification.sample-reports.store') }}" enctype="multipart/form-data">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                    @endif

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Report type</label>
                            <select name="report_type" class="form-select" required>
                                <option value="tenant" @selected(old('report_type', $sample->report_type) === 'tenant')>Tenant sample</option>
                                <option value="owner" @selected(old('report_type', $sample->report_type) === 'owner')>Owner sample</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">City (optional)</label>
                            <select name="city_slug" class="form-select">
                                <option value="">All cities</option>
                                @foreach($cityCatalog as $slug => $label)
                                    <option value="{{ $slug }}" @selected(old('city_slug', $sample->city_slug) === $slug)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Package (optional)</label>
                            <select name="package_id" class="form-select">
                                <option value="">All packages</option>
                                @foreach($packages as $pkg)
                                    <option value="{{ $pkg->id }}" @selected((string) old('package_id', $sample->package_id) === (string) $pkg->id)>
                                        {{ $pkg->name }} ({{ ucfirst($pkg->type) }} · {{ $cityCatalog[$pkg->city_slug] ?? $pkg->city_slug }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" maxlength="160" required value="{{ old('title', $sample->title) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sort order</label>
                            <input type="number" name="sort_order" class="form-control" min="0" max="999" value="{{ old('sort_order', $sample->sort_order ?? 0) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" maxlength="2000">{{ old('description', $sample->description) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Sample PDF {{ $isEdit ? '(replace optional)' : '' }}</label>
                            <div class="tv-upload-zone">
                                <input type="file" name="sample_pdf" class="form-control" accept="application/pdf,.pdf" {{ $isEdit ? '' : 'required' }}>
                                <span class="d-block mt-2 text-muted">PDF only, max 10 MB. No real customer data.</span>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="sample_active" @checked(old('is_active', $sample->is_active ?? true))>
                                <label class="form-check-label" for="sample_active">Active (visible on web)</label>
                            </div>
                        </div>
                    </div>

                    @if($isEdit && $sample->file_path)
                        <p class="small text-muted mt-2 mb-0">
                            Current file: {{ $sample->original_filename ?: 'sample.pdf' }}
                            — <a href="{{ route('trust-verification.sample-reports.download', $sample) }}" target="_blank" rel="noopener">Preview</a>
                        </p>
                    @endif

                    <button type="submit" class="btn tv-admin-btn-primary mt-3">{{ $isEdit ? 'Save changes' : 'Create sample' }}</button>
                </form>
            </div>
        </div>
    </section>
@endsection
