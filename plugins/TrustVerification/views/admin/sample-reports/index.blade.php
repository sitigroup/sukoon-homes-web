@extends('layouts.main')

@section('title')
    Trust Verification — Sample Reports
@endsection

@section('content')
    <section class="section tv-admin tv-admin-page">
        @include('trust-verification::admin.partials.tv-admin-theme')
        @include('trust-verification::admin.partials.nav')

        <header class="tv-admin-header">
            <div>
                <h1 class="tv-admin-header__title">Sample Reports</h1>
                <p class="tv-admin-header__meta mb-0">Reports → Sample PDFs shown before purchase (fictional data only).</p>
            </div>
            <a href="{{ route('trust-verification.sample-reports.create') }}" class="btn tv-admin-btn-primary">
                <i class="bi bi-plus-lg"></i> Add sample
            </a>
        </header>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="tv-help-block mb-3">
            Upload <strong>PDF only</strong> (max 10 MB). Use tenant and owner samples; optional city/package scopes more specific rows.
            Button copy is editable under <a href="{{ route('trust-verification.content.index', ['tab' => 'hub']) }}">Content → Hub</a>
            (<code>sample_report_*</code> keys).
        </div>

        <form method="get" class="tv-admin-filter row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-0">Type</label>
                <select name="report_type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="tenant" @selected(($filters['report_type'] ?? '') === 'tenant')>Tenant</option>
                    <option value="owner" @selected(($filters['report_type'] ?? '') === 'owner')>Owner</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-0">Status</label>
                <select name="is_active" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="1" @selected(($filters['is_active'] ?? '') === '1')>Active</option>
                    <option value="0" @selected(($filters['is_active'] ?? '') === '0')>Inactive</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn tv-admin-btn-primary flex-grow-1">Apply</button>
                <a href="{{ route('trust-verification.sample-reports.index') }}" class="btn tv-admin-btn-secondary">Reset</a>
            </div>
        </form>

        <p class="tv-admin-section-title">Included checks (shown in preview modal)</p>
        <ul class="small text-muted mb-3">
            @foreach($defaultChecks as $check)
                <li>{{ $check }}</li>
            @endforeach
        </ul>

        <div class="tv-admin-card">
            <div class="tv-admin-card__body tv-admin-card__body--flush">
                <div class="table-responsive">
                    <table class="table table-striped align-middle tv-admin-table mb-0">
                        <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Scope</th>
                            <th>PDF</th>
                            <th>Uploaded</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($samples as $sample)
                            <tr>
                                <td>
                                    <strong>{{ $sample->title }}</strong>
                                    @if($sample->description)
                                        <br><span class="small text-muted">{{ \Illuminate\Support\Str::limit($sample->description, 80) }}</span>
                                    @endif
                                </td>
                                <td>{{ ucfirst($sample->report_type) }}</td>
                                <td class="small">
                                    @if($sample->city_slug)
                                        {{ $cityCatalog[$sample->city_slug] ?? $sample->city_slug }}
                                    @else
                                        <span class="text-muted">All cities</span>
                                    @endif
                                    <br>
                                    @if($sample->package)
                                        {{ $sample->package->name }}
                                    @else
                                        <span class="text-muted">All packages</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sample->file_path)
                                        <span class="tv-admin-chip-success">Uploaded</span>
                                        <br><span class="small text-muted">{{ number_format(($sample->file_size_bytes ?? 0) / 1024, 1) }} KB</span>
                                    @else
                                        <span class="tv-admin-chip-muted">Missing</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @if($sample->created_at)
                                        <span class="tv-admin-chip-muted">{{ $sample->created_at->format('d M Y') }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($sample->is_active)
                                        <span class="tv-admin-chip-success">Active</span>
                                    @else
                                        <span class="tv-admin-chip-muted">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    @if($sample->file_path)
                                        <a href="{{ route('trust-verification.sample-reports.download', $sample) }}" class="btn btn-sm tv-admin-btn-secondary" target="_blank" rel="noopener">Preview</a>
                                    @endif
                                    <a href="{{ route('trust-verification.sample-reports.edit', $sample) }}" class="btn btn-sm tv-admin-btn-secondary">Edit</a>
                                    <form method="post" action="{{ route('trust-verification.sample-reports.destroy', $sample) }}" class="d-inline" onsubmit="return confirm('Delete this sample report?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm tv-admin-btn-secondary">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="tv-admin-empty-state">
                                        <div class="tv-admin-empty-state__icon"><i class="bi bi-file-earmark-pdf"></i></div>
                                        <p class="mb-0">No sample reports yet. Add tenant and owner PDFs so customers can preview before purchase.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
