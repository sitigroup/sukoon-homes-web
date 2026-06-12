@extends('layouts.main')

@section('title', __('seo-engine::seo_engine.dashboard_title'))

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
    @include('seo-engine::admin.seo-engine.partials.nav', ['active' => 'dashboard'])
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">{{ __('seo-engine::seo_engine.stat_indexable') }}</h6>
                    <h3 class="mb-0">{{ number_format($stats['indexable_count']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">{{ __('seo-engine::seo_engine.stat_missing_content') }}</h6>
                    <h3 class="mb-0">{{ number_format($stats['missing_content_count']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">{{ __('seo-engine::seo_engine.stat_redirect_hits') }}</h6>
                    <h3 class="mb-0">{{ number_format($stats['redirect_hits']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">{{ __('seo-engine::seo_engine.stat_qa_drafts') }}</h6>
                    <h3 class="mb-0">{{ number_format($stats['qa_draft_count']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-1">{{ __('seo-engine::seo_engine.stat_404_week') }}</h6>
                    <h3 class="mb-0">{{ number_format($stats['404_hits_week']) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">{{ __('seo-engine::seo_engine.cron_timestamps') }}</h5></div>
        <div class="card-body">
            <ul class="list-unstyled mb-0">
                <li><strong>{{ __('seo-engine::seo_engine.cron_generate_pages') }}:</strong>
                    {{ $cron['generate_pages'] ?: __('seo-engine::seo_engine.never') }}</li>
                <li><strong>{{ __('seo-engine::seo_engine.cron_build_sitemaps') }}:</strong>
                    {{ $cron['build_sitemaps'] ?: __('seo-engine::seo_engine.never') }}</li>
                <li><strong>{{ __('seo-engine::seo_engine.cron_generate_content') }}:</strong>
                    {{ $cron['generate_content'] ?: __('seo-engine::seo_engine.never') }}</li>
                <li><strong>{{ __('seo-engine::seo_engine.cron_404_digest') }}:</strong>
                    {{ $cron['404_digest'] ?: __('seo-engine::seo_engine.never') }}</li>
            </ul>
        </div>
    </div>

    @if(!empty($digest404['top']))
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">{{ __('seo-engine::seo_engine.digest_404_title') }}</h5></div>
        <div class="card-body">
            <p class="text-muted small">{{ __('seo-engine::seo_engine.digest_404_meta', ['paths' => $digest404['total_paths'] ?? 0, 'hits' => $digest404['total_hits'] ?? 0]) }}</p>
            <ul class="list-unstyled mb-0">
                @foreach(array_slice($digest404['top'] ?? [], 0, 10) as $row)
                    <li><code>{{ $row['path'] ?? '' }}</code> — {{ $row['hits'] ?? 0 }} hits</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">{{ __('seo-engine::seo_engine.indexnow_log_title') }}</h5></div>
        <div class="card-body">
            @forelse($indexnowLog as $entry)
                <div class="small mb-1">
                    <span class="badge bg-{{ ($entry['status'] ?? '') === 'ok' ? 'success' : 'secondary' }}">{{ $entry['status'] ?? '—' }}</span>
                    {{ $entry['url'] ?? '' }}
                    <span class="text-muted">{{ $entry['at'] ?? '' }}</span>
                </div>
            @empty
                <p class="text-muted mb-0">{{ __('seo-engine::seo_engine.indexnow_log_empty') }}</p>
            @endforelse
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <form method="post" action="{{ route('seo-engine.regenerate-pages') }}">
            @csrf
            <button type="submit" class="btn btn-primary">{{ __('seo-engine::seo_engine.btn_regenerate_pages') }}</button>
        </form>
        <form method="post" action="{{ route('seo-engine.regenerate-sitemaps') }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary">{{ __('seo-engine::seo_engine.btn_regenerate_sitemaps') }}</button>
        </form>
    </div>
</section>
@endsection
