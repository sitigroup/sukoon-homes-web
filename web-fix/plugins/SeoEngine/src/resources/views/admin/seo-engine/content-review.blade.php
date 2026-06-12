@extends('layouts.main')

@section('title', __('seo-engine::seo_engine.content_review_title'))

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
    @include('seo-engine::admin.seo-engine.partials.nav', ['active' => 'content'])
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <div class="mb-3">
        <a href="{{ route('seo-engine.content.index', ['status' => 'pending']) }}" class="btn btn-sm {{ $status === 'pending' ? 'btn-primary' : 'btn-outline-primary' }}">Pending</a>
        <a href="{{ route('seo-engine.content.index', ['status' => 'approved']) }}" class="btn btn-sm {{ $status === 'approved' ? 'btn-primary' : 'btn-outline-primary' }}">Approved</a>
        <a href="{{ route('seo-engine.content.index', ['status' => 'all']) }}" class="btn btn-sm {{ $status === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">All with content</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Path</th>
                        <th>Status</th>
                        <th>Generated</th>
                        <th>Preview</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pages as $page)
                        <tr>
                            <td><code>{{ $page->path }}</code></td>
                            <td>{{ $page->content_review_status ?? '—' }}</td>
                            <td>{{ optional($page->content_generated_at)->format('Y-m-d H:i') }}</td>
                            <td class="small">{{ \Illuminate\Support\Str::limit(strip_tags($page->intro_html), 120) }}</td>
                            <td class="text-nowrap">
                                @if($page->content_review_status === 'pending')
                                    <form method="post" action="{{ route('seo-engine.content.approve', $page) }}" class="d-inline">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
                                @endif
                                @if(!$page->lock_content)
                                    <form method="post" action="{{ route('seo-engine.content.regenerate', $page) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-secondary">Regenerate</button></form>
                                @endif
                                <form method="post" action="{{ route('seo-engine.content.lock', $page) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-dark">Lock</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted">No pages in this queue.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pages->hasPages())
            <div class="card-footer">{{ $pages->links() }}</div>
        @endif
    </div>
</section>
@endsection
