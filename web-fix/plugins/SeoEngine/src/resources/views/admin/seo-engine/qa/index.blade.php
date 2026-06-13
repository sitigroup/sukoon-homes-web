@extends('layouts.main')

@section('title', __('seo-engine::seo_engine.qa_title'))

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

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('seo-engine.qa.create') }}" class="btn btn-primary">{{ __('seo-engine::seo_engine.qa_add') }}</a>
    </div>

    <form method="get" class="row g-2 mb-3">
        <div class="col-md-3">
            <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="{{ __('seo-engine::seo_engine.search') }}">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">{{ __('seo-engine::seo_engine.qa_all_statuses') }}</option>
                <option value="draft" @selected($status === 'draft')>draft</option>
                <option value="published" @selected($status === 'published')>published</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">{{ __('seo-engine::seo_engine.qa_all_categories') }}</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-secondary w-100">{{ __('seo-engine::seo_engine.filter') }}</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>{{ __('seo-engine::seo_engine.qa_question') }}</th>
                        <th>{{ __('seo-engine::seo_engine.qa_category') }}</th>
                        <th>{{ __('seo-engine::seo_engine.qa_status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                        <tr>
                            <td>{{ \Illuminate\Support\Str::limit($page->question, 80) }}</td>
                            <td><code>{{ $page->category }}/{{ $page->slug }}</code></td>
                            <td><span class="badge bg-{{ $page->status === 'published' ? 'success' : 'secondary' }}">{{ $page->status }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('seo-engine.qa.edit', $page) }}" class="btn btn-sm btn-outline-primary">{{ __('seo-engine::seo_engine.qa_edit') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted">{{ __('seo-engine::seo_engine.qa_empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('seo-engine::admin.seo-engine.partials.pagination', ['paginator' => $pages])
    </div>
</section>
@endsection
