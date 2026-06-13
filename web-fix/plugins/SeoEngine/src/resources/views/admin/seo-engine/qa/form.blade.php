@extends('layouts.main')

@section('title', $page->exists ? __('seo-engine::seo_engine.qa_edit') : __('seo-engine::seo_engine.qa_add'))

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
    @include('seo-engine::admin.seo-engine.partials.nav', ['active' => 'qa'])
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('warning')) <div class="alert alert-warning">{{ session('warning') }}</div> @endif

    <form method="post" action="{{ $page->exists ? route('seo-engine.qa.update', $page) : route('seo-engine.qa.store') }}">
        @csrf
        @if($page->exists) @method('PUT') @endif

        <div class="card mb-3">
            <div class="card-header"><strong>{{ __('seo-engine::seo_engine.qa_answer_first') }}</strong></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">{{ __('seo-engine::seo_engine.qa_question') }} (H1)</label>
                    <input type="text" name="question" class="form-control @error('question') is-invalid @enderror"
                        value="{{ old('question', $page->question) }}" maxlength="512" required>
                    @error('question') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('seo-engine::seo_engine.qa_direct_answer') }} (≤60 words, answer box)</label>
                    <textarea name="direct_answer" class="form-control @error('direct_answer') is-invalid @enderror" rows="3" maxlength="500">{{ old('direct_answer', $page->direct_answer) }}</textarea>
                    @error('direct_answer') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('seo-engine::seo_engine.qa_body') }}</label>
                    <textarea name="body_html" class="form-control font-monospace @error('body_html') is-invalid @enderror" rows="12">{{ old('body_html', $page->body_html) }}</textarea>
                    @error('body_html') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">{{ __('seo-engine::seo_engine.qa_category') }}</label>
                        <input type="text" name="category" class="form-control @error('category') is-invalid @enderror"
                            value="{{ old('category', $page->category) }}" placeholder="rent-agreements" pattern="[a-z0-9-]+" required>
                        @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('seo-engine::seo_engine.qa_slug') }}</label>
                        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                            value="{{ old('slug', $page->slug) }}" placeholder="auto-from-question">
                        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">{{ __('seo-engine::seo_engine.qa_status') }}</label>
                        <select name="status" class="form-select">
                            <option value="draft" @selected(old('status', $page->status) === 'draft')>draft</option>
                            <option value="published" @selected(old('status', $page->status) === 'published')>published</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">{{ __('seo-engine::seo_engine.qa_related_links') }}</label>
                    <textarea name="related_rent_links" class="form-control font-monospace" rows="4" placeholder="/rent/barmer/">{{ old('related_rent_links', is_array($page->related_rent_links) ? implode("\n", $page->related_rent_links) : '') }}</textarea>
                    <div class="form-text">{{ __('seo-engine::seo_engine.qa_related_links_help') }}</div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">{{ __('seo-engine::seo_engine.btn_save') }}</button>
            <a href="{{ route('seo-engine.qa.index') }}" class="btn btn-outline-secondary">{{ __('seo-engine::seo_engine.qa_back') }}</a>
            @if($page->exists)
                <button type="submit" formaction="{{ route('seo-engine.qa.destroy', $page) }}" formmethod="post"
                    class="btn btn-outline-danger ms-auto"
                    onclick="return confirm('Delete this guide?');">
                    @csrf @method('DELETE') {{ __('seo-engine::seo_engine.qa_delete') }}
                </button>
            @endif
        </div>
    </form>
</section>
@endsection
