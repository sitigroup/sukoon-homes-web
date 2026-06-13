@extends('layouts.main')

@section('title', __('seo-engine::seo_engine.pages_title'))

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
    @include('seo-engine::admin.seo-engine.partials.nav', ['active' => 'pages'])
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">{{ __('seo-engine::seo_engine.search') }}</label>
                    <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="/rent/barmer/">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('seo-engine::seo_engine.page_type') }}</label>
                    <select name="type" class="form-select">
                        <option value="">All</option>
                        @foreach($types as $t)
                            <option value="{{ $t }}" @selected($type === $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('seo-engine::seo_engine.indexable') }}</label>
                    <select name="indexable" class="form-select">
                        <option value="">All</option>
                        <option value="1" @selected($indexable === '1')>Yes</option>
                        <option value="0" @selected($indexable === '0')>No</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('seo-engine::seo_engine.content') }}</label>
                    <select name="content" class="form-select">
                        <option value="">All</option>
                        <option value="missing" @selected($content === 'missing')>Missing</option>
                        <option value="has" @selected($content === 'has')>Has content</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('seo-engine::seo_engine.filter') }}</button>
                    <a href="{{ route('seo-engine.pages.export') }}" class="btn btn-outline-secondary">{{ __('seo-engine::seo_engine.export_csv') }}</a>
                    <a href="{{ route('seo-engine.content-generate.index') }}" class="btn btn-outline-primary">{{ __('seo-engine::seo_engine.nav_content_generate') }}</a>
                </div>
            </form>
        </div>
    </div>

    <form method="post" action="{{ route('seo-engine.pages.bulk') }}">
        @csrf
        <div class="card">
            <div class="card-header d-flex flex-wrap gap-2 align-items-center">
                <h5 class="mb-0 me-auto">{{ __('seo-engine::seo_engine.pages_list') }}</h5>
                <select name="action" class="form-select form-select-sm" style="max-width:220px" required>
                    <option value="regenerate_meta">{{ __('seo-engine::seo_engine.bulk_regenerate_meta') }}</option>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('seo-engine::seo_engine.bulk_apply') }}</button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="check-all"></th>
                            <th>Path</th>
                            <th>Type</th>
                            <th>Listings</th>
                            <th>Quality</th>
                            <th>Index</th>
                            <th>Content</th>
                            @if(!empty($gscPages))
                            <th title="Google Search Console (28d)">GSC</th>
                            @endif
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pages as $page)
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="{{ $page->id }}" class="page-check"></td>
                                <td><code>{{ $page->path }}</code></td>
                                <td>{{ $page->page_type }}</td>
                                <td>{{ $page->listing_count }}</td>
                                <td>{{ $page->quality_score }}</td>
                                <td>{{ $page->is_indexable ? 'Yes' : 'No' }}</td>
                                <td>{{ $page->intro_html ? 'Yes' : '—' }}</td>
                                @if(!empty($gscPages))
                                @php $gsc = $gscPages[$page->path] ?? null; @endphp
                                <td class="small text-muted">
                                    @if($gsc)
                                        {{ $gsc['clicks'] }}/{{ $gsc['impressions'] }}<br>@if($gsc['position'])#{{ $gsc['position'] }}@endif
                                    @else — @endif
                                </td>
                                @endif
                                <td><a href="{{ route('seo-engine.pages.edit', $page) }}" class="btn btn-sm btn-outline-secondary">Edit</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted">No pages found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($pages->hasPages())
                <div class="card-footer">{{ $pages->links() }}</div>
            @endif
        </div>
    </form>
</section>
<script>
document.getElementById('check-all')?.addEventListener('change', function () {
    document.querySelectorAll('.page-check').forEach(cb => cb.checked = this.checked);
});
</script>
@endsection
