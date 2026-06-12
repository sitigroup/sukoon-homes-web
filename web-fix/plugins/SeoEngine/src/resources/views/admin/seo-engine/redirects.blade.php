@extends('layouts.main')

@section('title', __('seo-engine::seo_engine.redirects_title'))

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
<section class="section pt-2">
    @include('seo-engine::admin.seo-engine.partials.nav', ['active' => 'redirects'])
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">{{ __('seo-engine::seo_engine.redirect_add') }}</h5></div>
        <div class="card-body">
            <form method="post" action="{{ route('seo-engine.redirects.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">{{ __('seo-engine::seo_engine.redirect_from') }}</label>
                    <input type="text" name="from_path" class="form-control" placeholder="/property-details/old-slug/" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('seo-engine::seo_engine.redirect_to') }}</label>
                    <input type="text" name="to_path" class="form-control" placeholder="/property-details/new-slug/" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('seo-engine::seo_engine.redirect_status') }}</label>
                    <select name="status_code" class="form-select">
                        <option value="301">301</option>
                        <option value="302">302</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">{{ __('seo-engine::seo_engine.redirect_add') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5 class="mb-0">{{ __('seo-engine::seo_engine.redirect_import_csv') }}</h5></div>
        <div class="card-body">
            <p class="text-muted small mb-2">{{ __('seo-engine::seo_engine.redirect_csv_help') }}</p>
            <form method="post" action="{{ route('seo-engine.redirects.import') }}" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap">
                @csrf
                <input type="file" name="csv_file" accept=".csv,text/csv" class="form-control" style="max-width:320px" required>
                <button type="submit" class="btn btn-outline-secondary">{{ __('seo-engine::seo_engine.redirect_import_csv') }}</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">{{ __('seo-engine::seo_engine.nav_redirects') }}</h5>
            <form method="get" class="d-flex gap-2">
                <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm" placeholder="Search paths">
                <button type="submit" class="btn btn-sm btn-outline-primary">Search</button>
            </form>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>{{ __('seo-engine::seo_engine.redirect_from') }}</th>
                        <th>{{ __('seo-engine::seo_engine.redirect_to') }}</th>
                        <th>{{ __('seo-engine::seo_engine.redirect_status') }}</th>
                        <th>{{ __('seo-engine::seo_engine.redirect_hits') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($redirects as $redirect)
                        <tr>
                            <td><code>{{ $redirect->from_path }}</code></td>
                            <td>
                                <form method="post" action="{{ route('seo-engine.redirects.update', $redirect) }}" class="d-flex gap-1 flex-wrap">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="from_path" value="{{ $redirect->from_path }}">
                                    <input type="text" name="to_path" value="{{ $redirect->to_path }}" class="form-control form-control-sm" style="min-width:200px">
                                    <select name="status_code" class="form-select form-select-sm" style="width:90px">
                                        <option value="301" @selected($redirect->status_code == 301)>301</option>
                                        <option value="302" @selected($redirect->status_code == 302)>302</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                </form>
                            </td>
                            <td>{{ $redirect->status_code }}</td>
                            <td>{{ number_format($redirect->hits) }}</td>
                            <td>
                                <form method="post" action="{{ route('seo-engine.redirects.destroy', $redirect) }}" onsubmit="return confirm('Delete redirect?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">&times;</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No redirects yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $redirects->links() }}
        </div>
    </div>
</section>
@endsection
