@extends('layouts.main')

@section('title', __('seo-engine::seo_engine.content_generate_title'))

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
    @include('seo-engine::admin.seo-engine.partials.nav', ['active' => 'content-generate'])
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <p class="text-muted mb-3">
        {{ __('seo-engine::seo_engine.content_generate_intro', ['provider' => ucfirst($provider)]) }}
    </p>

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
                <div class="col-md-3">
                    <label class="form-label">{{ __('seo-engine::seo_engine.content_generate_area') }}</label>
                    <select name="area" class="form-select">
                        <option value="">All areas</option>
                        @foreach($areas as $a)
                            <option value="{{ $a }}" @selected($area === $a)>{{ $a }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-4">
                        <input type="checkbox" name="missing_only" value="1" class="form-check-input" id="missing_only" @checked($missingOnly)>
                        <label class="form-check-label" for="missing_only">{{ __('seo-engine::seo_engine.content_generate_missing_only') }}</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">{{ __('seo-engine::seo_engine.filter') }}</button>
                </div>
            </form>
        </div>
    </div>

    <form method="post" action="{{ route('seo-engine.content-generate.confirm') }}" id="generate-form">
        @csrf
        <input type="hidden" name="prompt_template" id="prompt_template_hidden" value="">

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">{{ __('seo-engine::seo_engine.content_generate_prompt') }}</h5>
            </div>
            <div class="card-body">
                <textarea id="prompt_template_editor" class="form-control font-monospace" rows="12">{{ old('prompt_template', $defaultPrompt) }}</textarea>
                <div class="form-text">{{ __('seo-engine::seo_engine.content_generate_prompt_help') }}</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex flex-wrap gap-2 align-items-center">
                <h5 class="mb-0 me-auto">{{ __('seo-engine::seo_engine.pages_list') }}</h5>
                <span class="text-muted small" id="selected-count">{{ __('seo-engine::seo_engine.content_generate_selected', ['count' => 0]) }}</span>
                <button type="submit" class="btn btn-sm btn-primary" id="review-btn" disabled>
                    {{ __('seo-engine::seo_engine.content_generate_review') }}
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="check-all" title="Select all on this page"></th>
                            <th>Path</th>
                            <th>Type</th>
                            <th>Listings</th>
                            <th>Content</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pages as $page)
                            @php
                                $eligibleAi = (int) $page->listing_count >= $minListings;
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" name="ids[]" value="{{ $page->id }}" class="page-check">
                                </td>
                                <td><code>{{ $page->path }}</code></td>
                                <td>{{ $page->page_type }}</td>
                                <td>{{ $page->listing_count }}</td>
                                <td>
                                    @if($page->intro_html)
                                        <span class="text-muted">{{ __('seo-engine::seo_engine.content_generate_has_content') }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($eligibleAi)
                                        <span class="badge bg-primary">{{ ucfirst($provider) }}</span>
                                    @else
                                        <span class="badge bg-secondary">Template</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No pages found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @include('seo-engine::admin.seo-engine.partials.pagination', ['paginator' => $pages])
        </div>
    </form>
</section>
<script>
(function () {
    const checks = () => Array.from(document.querySelectorAll('.page-check'));
    const countEl = document.getElementById('selected-count');
    const reviewBtn = document.getElementById('review-btn');
    const checkAll = document.getElementById('check-all');
    const promptEditor = document.getElementById('prompt_template_editor');
    const promptHidden = document.getElementById('prompt_template_hidden');
    const form = document.getElementById('generate-form');

    function updateCount() {
        const n = checks().filter(cb => cb.checked).length;
        countEl.textContent = @json(__('seo-engine::seo_engine.content_generate_selected', ['count' => ':count'])).replace(':count', String(n));
        reviewBtn.disabled = n === 0;
    }

    checkAll?.addEventListener('change', function () {
        checks().forEach(cb => { cb.checked = this.checked; });
        updateCount();
    });

    checks().forEach(cb => cb.addEventListener('change', updateCount));

    form?.addEventListener('submit', function () {
        promptHidden.value = promptEditor.value;
    });

    updateCount();
})();
</script>
@endsection
