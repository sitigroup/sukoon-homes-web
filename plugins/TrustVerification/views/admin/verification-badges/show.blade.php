@extends('layouts.main')
@section('title') Badge {{ $badge->badge_number }} @endsection
@section('content')
<section class="section tv-admin tv-admin-page">
    @include('trust-verification::admin.partials.tv-admin-theme')
    @include('trust-verification::admin.partials.nav')

    <header class="tv-admin-header">
        <div>
            <h1 class="tv-admin-header__title">{{ $badge->displayTitle() }}</h1>
            <p class="tv-admin-header__meta mb-0"><code>{{ $badge->badge_number }}</code> · Customer #{{ $badge->customer_id }}</p>
        </div>
        @if($order)
            <a href="{{ route('trust-verification.orders.show', $order) }}" class="btn tv-admin-btn-secondary">View order</a>
        @endif
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="tv-admin-card">
                <div class="tv-admin-card__body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4">Status</dt><dd class="col-sm-8">{{ ucfirst($badge->status) }}</dd>
                        <dt class="col-sm-4">Type</dt><dd class="col-sm-8">{{ $badge->badge_type }}</dd>
                        <dt class="col-sm-4">Issued</dt><dd class="col-sm-8">{{ $badge->issued_at?->format('d M Y H:i') ?? '—' }}</dd>
                        <dt class="col-sm-4">Expires</dt><dd class="col-sm-8">{{ $badge->expires_at?->format('d M Y H:i') ?? '—' }}</dd>
                        <dt class="col-sm-4">Revoked</dt><dd class="col-sm-8">{{ $badge->revoked_at?->format('d M Y H:i') ?? '—' }}</dd>
                        @if($badge->revoke_reason)
                            <dt class="col-sm-4">Revoke reason</dt><dd class="col-sm-8">{{ $badge->revoke_reason }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="tv-admin-card">
                <div class="tv-admin-card__body">
                    <h2 class="h6">Eligibility</h2>
                    @if(count($blockers) === 0)
                        <p class="text-success mb-0">All issuance requirements met.</p>
                    @else
                        <ul class="mb-0 small">
                            @foreach($blockers as $line)
                                <li>{{ $line }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($tvPermissions['update'] ?? false)
        <div class="tv-admin-card mt-3">
            <div class="tv-admin-card__body d-flex flex-wrap gap-2">
                @if($badge->status === 'pending')
                    <form method="post" action="{{ route('trust-verification.verification-badges.verify', $badge) }}">
                        @csrf
                        <button type="submit" class="btn tv-admin-btn-primary">Mark verified</button>
                    </form>
                @endif
                @if(in_array($badge->status, ['verified', 'expired'], true))
                    <form method="post" action="{{ route('trust-verification.verification-badges.renew', $badge) }}">
                        @csrf
                        <button type="submit" class="btn tv-admin-btn-secondary">Renew (+12 months)</button>
                    </form>
                @endif
                @if($badge->status !== 'revoked')
                    <form method="post" action="{{ route('trust-verification.verification-badges.revoke', $badge) }}" class="d-flex gap-2 align-items-start"
                          onsubmit="return confirm('Revoke this badge?');">
                        @csrf
                        <input type="text" name="revoke_reason" class="form-control form-control-sm" placeholder="Revoke reason" required maxlength="500" style="min-width:220px">
                        <button type="submit" class="btn btn-outline-danger btn-sm">Revoke</button>
                    </form>
                @endif
                <a href="{{ route('trust-verification.verification-badges.download', $badge) }}" class="btn tv-admin-btn-secondary" target="_blank" rel="noopener">Download certificate</a>
            </div>
        </div>
    @endif
</section>
@endsection
