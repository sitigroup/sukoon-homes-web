@extends('layouts.main')

@section('title', __('seo-engine::seo_engine.page_edit_title'))

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-8 order-md-1 order-last">
                <h4>{{ __('seo-engine::seo_engine.page_edit_title') }}</h4>
                <p class="text-muted mb-0"><code>{{ $page->path }}</code></p>
            </div>
        </div>
    </div>
@endsection

@section('content')
<section class="section pt-2">
    @include('seo-engine::admin.seo-engine.partials.nav', ['active' => 'pages'])
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="post" action="{{ route('seo-engine.pages.update', $page) }}">
        @csrf
        @method('PUT')
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Meta overrides</h5></div>
            <div class="card-body row g-3">
                <div class="col-12">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $page->title) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">H1</label>
                    <input type="text" name="h1" class="form-control" value="{{ old('h1', $page->h1) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Meta description</label>
                    <textarea name="meta_description" class="form-control" rows="2">{{ old('meta_description', $page->meta_description) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Intro HTML</label>
                    <textarea name="intro_html" class="form-control" rows="6">{{ old('intro_html', $page->intro_html) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">FAQ JSON</label>
                    <textarea name="faq_json" class="form-control font-monospace" rows="8">{{ old('faq_json', $page->faq_json ? json_encode($page->faq_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '') }}</textarea>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" name="is_indexable" value="1" class="form-check-input" id="is_indexable" @checked(old('is_indexable', $page->is_indexable))>
                        <label class="form-check-label" for="is_indexable">Force indexable</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-check">
                        <input type="checkbox" name="lock_content" value="1" class="form-check-input" id="lock_content" @checked(old('lock_content', $page->lock_content))>
                        <label class="form-check-label" for="lock_content">Lock content (skip regeneration)</label>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ __('seo-engine::seo_engine.btn_save') }}</button>
                <a href="{{ route('seo-engine.pages.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Registry stats</h5></div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Type</dt><dd class="col-sm-9">{{ $page->page_type }}</dd>
                <dt class="col-sm-3">Listings</dt><dd class="col-sm-9">{{ $page->listing_count }}</dd>
                <dt class="col-sm-3">Quality</dt><dd class="col-sm-9">{{ $page->quality_score }}</dd>
                <dt class="col-sm-3">Updated</dt><dd class="col-sm-9">{{ $page->updated_at }}</dd>
            </dl>
        </div>
    </div>
</section>
@endsection
