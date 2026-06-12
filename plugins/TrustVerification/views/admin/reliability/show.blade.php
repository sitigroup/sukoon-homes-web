@extends('layouts.main')
@section('title') Tenant Reliability #{{ $customerId }} @endsection
@section('content')
<section class="section tv-admin tv-admin-page">
    @include('trust-verification::admin.partials.tv-admin-theme')
    @include('trust-verification::admin.partials.nav')

    <header class="tv-admin-header">
        <div>
            <h1 class="tv-admin-header__title">Customer #{{ $customerId }} — tenant reliability</h1>
            <p class="tv-admin-header__meta mb-0">Owner-visible preview (sanitized) vs stored summary.</p>
        </div>
        @if($tvPermissions['update'] ?? false)
            <form method="post" action="{{ route('trust-verification.reliability.refresh', $customerId) }}" class="m-0">
                @csrf
                <button type="submit" class="btn tv-admin-btn-primary btn-sm">Refresh</button>
            </form>
        @endif
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="tv-admin-card h-100">
                <div class="tv-admin-card__body">
                    <div class="small text-muted">Verification completion</div>
                    <div class="h3 mb-0">{{ $record?->verification_completion ?? 0 }}%</div>
                    <div class="mt-2">{{ $levels[$record?->reliability_level ?? ''] ?? '—' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="tv-admin-card h-100">
                <div class="tv-admin-card__body">
                    <h2 class="h6">Owner-safe API preview</h2>
                    @if($ownerSafe)
                        <ul class="mb-0">
                            <li><strong>{{ $ownerSafe['reliability_label'] ?? '—' }}</strong></li>
                            <li>Completion: {{ $ownerSafe['verification_completion'] ?? 0 }}%</li>
                            <li>Status: {{ $ownerSafe['verification_status'] ?? '—' }}</li>
                            @foreach($ownerSafe['checks'] ?? [] as $check)
                                <li>{{ ($check['verified'] ?? false) ? '✓' : '○' }} {{ $check['label'] ?? '' }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0">Not available (hidden or no tenant order).</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($tvPermissions['update'] ?? false && $record)
        <form method="post" action="{{ route('trust-verification.reliability.public', $customerId) }}" class="mb-4">
            @csrf
            <div class="form-check">
                <input type="hidden" name="public_visible" value="0">
                <input class="form-check-input" type="checkbox" name="public_visible" value="1" id="pubVis" @checked($record->public_visible)>
                <label class="form-check-label" for="pubVis">Visible to owners on Homes</label>
            </div>
            <button type="submit" class="btn tv-admin-btn-secondary btn-sm mt-2">Save visibility</button>
        </form>
    @endif

    <div class="tv-admin-card">
        <div class="tv-admin-card__body">
            <h2 class="h6">Tenant verification orders</h2>
            <ul class="mb-0">
                @forelse($orders as $o)
                    <li>
                        <a href="{{ route('trust-verification.orders.show', $o) }}">{{ $o->order_number }}</a>
                        — {{ $o->status }}
                    </li>
                @empty
                    <li class="text-muted">No tenant orders</li>
                @endforelse
            </ul>
        </div>
    </div>
</section>
@endsection
