@extends('layouts.main')

@section('title', __('seo-engine::seo_engine.performance_title'))

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
    @include('seo-engine::admin.seo-engine.partials.nav', ['active' => 'performance'])
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">{{ __('seo-engine::seo_engine.leads_new') }}</h6>
                <h3>{{ number_format($leadCounts['new']) }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">{{ __('seo-engine::seo_engine.leads_total') }}</h6>
                <h3>{{ number_format($leadCounts['total']) }}</h3>
            </div></div>
        </div>
        @if(!empty($metrics['totals']))
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">GSC clicks (28d)</h6>
                <h3>{{ number_format($metrics['totals']['clicks'] ?? 0) }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">GSC impressions (28d)</h6>
                <h3>{{ number_format($metrics['totals']['impressions'] ?? 0) }}</h3>
            </div></div>
        </div>
        @endif
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
            <h5 class="mb-0">Google Search Console</h5>
            @if($gscConnected)
                <form method="post" action="{{ route('seo-engine.performance.gsc-sync') }}" class="ms-auto">@csrf<button class="btn btn-sm btn-outline-primary">Sync now</button></form>
                <form method="post" action="{{ route('seo-engine.performance.gsc-disconnect') }}">@csrf<button class="btn btn-sm btn-outline-danger">Disconnect</button></form>
            @elseif($gscConfigured)
                <a href="{{ route('seo-engine.performance.gsc-connect') }}" class="btn btn-sm btn-primary ms-auto">Connect GSC</a>
            @else
                <span class="text-muted small ms-auto">{{ __('seo-engine::seo_engine.gsc_configure_hint') }}</span>
            @endif
        </div>
        <div class="card-body">
            @if($metrics['awaiting'] ?? true)
                <p class="text-muted mb-0">{{ __('seo-engine::seo_engine.gsc_awaiting') }}</p>
            @else
                <p class="small text-muted">Period: {{ $metrics['period']['start'] ?? '' }} → {{ $metrics['period']['end'] ?? '' }}</p>

                @if(!empty($metrics['top_queries']))
                <h6 class="mt-3">Top queries</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Query</th><th>Clicks</th><th>Impressions</th><th>Position</th></tr></thead>
                        <tbody>
                            @foreach($metrics['top_queries'] as $row)
                                <tr>
                                    <td>{{ $row['query'] }}</td>
                                    <td>{{ $row['clicks'] }}</td>
                                    <td>{{ $row['impressions'] }}</td>
                                    <td>{{ $row['position'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                @if(!empty($metrics['top_pages']))
                <h6 class="mt-3">Top /rent/ pages</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Path</th><th>Clicks</th><th>Impressions</th><th>Position</th></tr></thead>
                        <tbody>
                            @foreach($metrics['top_pages'] as $row)
                                <tr>
                                    <td><code>{{ $row['path'] }}</code></td>
                                    <td>{{ $row['clicks'] }}</td>
                                    <td>{{ $row['impressions'] }}</td>
                                    <td>{{ $row['position'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                @if(!empty($metrics['low_ctr']))
                <h6 class="mt-3 text-warning">Low CTR alerts</h6>
                <ul class="small mb-0">
                    @foreach($metrics['low_ctr'] as $row)
                        <li><code>{{ $row['path'] }}</code> — {{ $row['impressions'] }} impressions, 0 clicks</li>
                    @endforeach
                </ul>
                @endif

                @if(!empty($metrics['movers']))
                <h6 class="mt-3">Biggest movers (position)</h6>
                <ul class="small mb-0">
                    @foreach($metrics['movers'] as $row)
                        <li><code>{{ $row['path'] }}</code> — {{ $row['delta'] > 0 ? '+' : '' }}{{ $row['delta'] }}</li>
                    @endforeach
                </ul>
                @endif
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">GA4</h5></div>
        <div class="card-body">
            @if($ga4Enabled && $ga4Id)
                <p class="mb-0">Tracking enabled: <code>{{ $ga4Id }}</code></p>
            @else
                <p class="text-muted mb-0">{{ __('seo-engine::seo_engine.ga4_disabled_hint') }}</p>
            @endif
        </div>
    </div>
</section>
@endsection
