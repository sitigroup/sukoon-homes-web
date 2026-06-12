@extends('layouts.main')
@section('title') Customer #{{ $customerId }} trust @endsection
@section('content')
<section class="section tv-admin tv-admin-page">
    @include('trust-verification::admin.partials.tv-admin-theme')
    @include('trust-verification::admin.partials.nav')
    @include('trust-verification::admin.partials.trust-nav')

    <a href="{{ route('trust-verification.trust.customers.index') }}" class="tv-link-back"><i class="bi bi-arrow-left"></i> Customer trust</a>

    <header class="tv-admin-header">
        <div>
            <h1 class="tv-admin-header__title">Customer #{{ $customerId }}</h1>
            <p class="tv-admin-header__meta mb-0">
                Sukoon Trust Score: <strong>{{ $score->trust_score ?? 0 }} / 100</strong>
                @if($score->last_calculated_at)
                    · Updated {{ $score->last_calculated_at->format('d M Y H:i') }}
                @endif
            </p>
        </div>
        @if($tvPermissions['update'] ?? false)
            <form method="post" action="{{ route('trust-verification.trust.customers.recalculate', $customerId) }}" class="m-0">
                @csrf
                <button type="submit" class="btn tv-admin-btn-primary">Recalculate</button>
            </form>
        @endif
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="tv-admin-card">
                <div class="tv-admin-card__header"><strong>Score breakdown</strong></div>
                <div class="tv-admin-card__body">
                    @php $components = $breakdown['components'] ?? []; @endphp
                    @forelse($components as $key => $comp)
                        <p class="mb-2">
                            {{ $comp['passed'] ?? false ? '✓' : '○' }}
                            {{ $comp['label'] ?? $key }}
                            <span class="text-muted">({{ $comp['points'] ?? 0 }} pts)</span>
                        </p>
                    @empty
                        <p class="text-muted mb-0">No breakdown yet. Recalculate after a completed order.</p>
                    @endforelse
                    @if(isset($breakdown['raw_total']))
                        <hr>
                        <p class="mb-0 small">Raw: {{ $breakdown['raw_total'] }} · Adjustment: {{ $score->manual_adjustment ?? 0 }}</p>
                    @endif
                </div>
            </div>

            @if($tvPermissions['update'] ?? false)
                <div class="tv-admin-card">
                    <div class="tv-admin-card__header"><strong>Manual override</strong></div>
                    <div class="tv-admin-card__body">
                        <form method="post" action="{{ route('trust-verification.trust.customers.adjustment', $customerId) }}" class="mb-3">
                            @csrf
                            <label class="form-label">Score adjustment (−100 to +100)</label>
                            <input type="number" name="manual_adjustment" class="form-control" value="{{ old('manual_adjustment', $score->manual_adjustment ?? 0) }}" min="-100" max="100">
                            <button type="submit" class="btn tv-admin-btn-primary mt-2">Apply adjustment</button>
                        </form>
                        <form method="post" action="{{ route('trust-verification.trust.customers.public', $customerId) }}">
                            @csrf
                            <div class="form-check">
                                <input type="hidden" name="public_visible" value="0">
                                <input type="checkbox" name="public_visible" value="1" class="form-check-input" id="pubv"
                                       @checked($score->public_visible ?? true) onchange="this.form.submit()">
                                <label class="form-check-label" for="pubv">Public trust visible</label>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-7">
            <div class="tv-admin-card">
                <div class="tv-admin-card__header"><strong>Badges</strong></div>
                <div class="tv-admin-card__body">
                    @forelse($assigned as $row)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                            <div>
                                <i class="bi {{ $row->badge?->icon }} me-1"></i>
                                <strong>{{ $row->badge?->name }}</strong>
                                <span class="text-muted small">{{ $row->auto_assigned ? 'auto' : 'manual' }}</span>
                            </div>
                            @if($tvPermissions['update'] ?? false)
                                <form method="post" action="{{ route('trust-verification.trust.customers.revoke-badge', [$customerId, $row]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm tv-admin-btn-secondary">Revoke</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted">No badges assigned.</p>
                    @endforelse

                    @if($tvPermissions['update'] ?? false)
                        <hr>
                        <form method="post" action="{{ route('trust-verification.trust.customers.assign-badge', $customerId) }}" class="row g-2">
                            @csrf
                            <div class="col-md-8">
                                <select name="badge_id" class="form-select" required>
                                    <option value="">Assign badge…</option>
                                    @foreach($badges as $b)
                                        @if(! $assigned->has($b->id))
                                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn tv-admin-btn-secondary w-100">Assign</button>
                            </div>
                            <div class="col-12">
                                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes (optional)">
                            </div>
                        </form>
                    @endif
                </div>
            </div>

            <div class="tv-admin-card">
                <div class="tv-admin-card__header"><strong>Recent orders</strong></div>
                <div class="tv-admin-card__body">
                    <ul class="list-unstyled mb-0">
                        @foreach($orders as $o)
                            <li class="mb-2">
                                <a href="{{ route('trust-verification.orders.show', $o) }}">{{ $o->order_number }}</a>
                                · {{ $o->order_type }} · {{ $o->status }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
