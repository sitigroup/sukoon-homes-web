@extends('layouts.main')

@section('title', __('seo-engine::seo_engine.templates_title'))

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
    @include('seo-engine::admin.seo-engine.partials.nav', ['active' => 'templates'])
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    <div class="mb-3 d-flex flex-wrap gap-2">
        @foreach($pageTypes as $type)
            <a href="{{ route('seo-engine.templates.index', ['page_type' => $type]) }}"
               class="btn btn-sm {{ $active === $type ? 'btn-primary' : 'btn-outline-primary' }}">{{ $type }}</a>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ $active }}</h5></div>
                <div class="card-body">
                    <p class="small text-muted">{{ __('seo-engine::seo_engine.template_variables') }}:
                        @foreach(\App\Plugins\SeoEngine\Services\SeoEngineTemplateService::VARIABLES as $v)<code class="me-1">{{ $v }}</code>@endforeach
                    </p>
                    <form method="post" action="{{ route('seo-engine.templates.store') }}">
                        @csrf
                        <input type="hidden" name="page_type" value="{{ $active }}">
                        <div class="mb-3">
                            <label class="form-label">Title template</label>
                            <input type="text" name="title_template" class="form-control" required
                                value="{{ old('title_template', $current['title_template'] ?? '') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">H1 template</label>
                            <input type="text" name="h1_template" class="form-control" required
                                value="{{ old('h1_template', $current['h1_template'] ?? '') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meta description template</label>
                            <textarea name="meta_description_template" class="form-control" rows="3">{{ old('meta_description_template', $current['meta_description_template'] ?? '') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">{{ __('seo-engine::seo_engine.btn_save') }}</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">{{ __('seo-engine::seo_engine.template_preview') }}</h5></div>
                <div class="card-body small">
                    <p><strong>Title:</strong> {{ $preview['title'] }}</p>
                    <p><strong>H1:</strong> {{ $preview['h1'] }}</p>
                    <p><strong>Meta:</strong> {{ $preview['meta_description'] }}</p>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('seo-engine::seo_engine.template_history') }}</h5></div>
                <ul class="list-group list-group-flush">
                    @forelse($history as $ver)
                        <li class="list-group-item small">
                            <div class="text-muted">{{ $ver->created_at }}</div>
                            <div>{{ Str::limit($ver->title_template, 80) }}</div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No versions yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</section>
@endsection
