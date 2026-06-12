@extends('layouts.main')
@section('title') Risk profile #{{ $customerId }} @endsection
@section('content')
@php
    use App\Plugins\TrustVerification\Services\TrustVerificationFraudRiskService;
@endphp
<section class="section tv-admin tv-admin-page">
    @include('trust-verification::admin.partials.tv-admin-theme')
    @include('trust-verification::admin.partials.nav')
    @include('trust-verification::admin.partials.risk-nav')

    <header class="tv-admin-header">
        <div>
            <h1 class="tv-admin-header__title">Customer #{{ $customerId }} — risk</h1>
            <p class="tv-admin-header__meta mb-0">Fraud signals timeline and manual controls (admin only).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('trust-verification.trust.customers.show', $customerId) }}" class="btn tv-admin-btn-secondary btn-sm">Trust profile</a>
            @if($tvPermissions['update'] ?? false)
                <form method="post" action="{{ route('trust-verification.risk.profiles.refresh', $customerId) }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn tv-admin-btn-primary btn-sm">Refresh risk</button>
                </form>
            @endif
        </div>
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="tv-admin-card h-100">
                <div class="tv-admin-card__body">
                    <div class="small text-muted">Risk score</div>
                    <div class="h3 mb-0">{{ $profile?->risk_score ?? 0 }} <span class="fs-6 text-muted">/ 100</span></div>
                    <span class="badge bg-{{ TrustVerificationFraudRiskService::levelBadgeClass($profile?->risk_level ?? 'low') }} mt-2">
                        {{ ucfirst($profile?->risk_level ?? 'low') }}
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="tv-admin-card h-100">
                <div class="tv-admin-card__body">
                    <div class="small text-muted">Trust score (public)</div>
                    <div class="h3 mb-0">{{ $trustScore?->trust_score ?? '—' }}</div>
                    <div class="small text-muted mt-2">Separate from fraud risk</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="tv-admin-card h-100">
                <div class="tv-admin-card__body">
                    <div class="small text-muted">Manual override</div>
                    <div class="h3 mb-0">{{ ($profile?->manual_override ?? 0) >= 0 ? '+' : '' }}{{ $profile?->manual_override ?? 0 }}</div>
                    <div class="small text-muted mt-2">Added to signal sum</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="tv-admin-card h-100">
                <div class="tv-admin-card__body">
                    <div class="small text-muted">Last calculated</div>
                    <div class="fw-semibold">{{ $profile?->last_calculated_at?->format('d M Y H:i') ?? '—' }}</div>
                    @if($profile?->notes)
                        <div class="small text-muted mt-2">{{ $profile->notes }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($tvPermissions['update'] ?? false)
        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="tv-admin-card">
                    <div class="tv-admin-card__body">
                        <h2 class="h6">Manual override</h2>
                        <form method="post" action="{{ route('trust-verification.risk.profiles.override', $customerId) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small">Override points (−100 to +100)</label>
                                <input type="number" name="manual_override" class="form-control form-control-sm"
                                       value="{{ old('manual_override', $profile?->manual_override ?? 0) }}" min="-100" max="100">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Notes</label>
                                <textarea name="notes" class="form-control form-control-sm" rows="2">{{ old('notes', $profile?->notes) }}</textarea>
                            </div>
                            <button type="submit" class="btn tv-admin-btn-save btn-sm">Save override</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="tv-admin-card">
                    <div class="tv-admin-card__body">
                        <h2 class="h6">Manual fraud flag</h2>
                        <form method="post" action="{{ route('trust-verification.risk.profiles.manual-flag', $customerId) }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small">Risk points (1–100)</label>
                                <input type="number" name="risk_points" class="form-control form-control-sm" min="1" max="100" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Order ID (optional)</label>
                                <input type="number" name="order_id" class="form-control form-control-sm" placeholder="tv_orders.id">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Notes</label>
                                <textarea name="notes" class="form-control form-control-sm" rows="2" required></textarea>
                            </div>
                            <button type="submit" class="btn tv-admin-btn-secondary btn-sm">Add flag</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="tv-admin-card mb-4">
        <div class="tv-admin-card__body--flush p-0">
            <div class="p-3 border-bottom">
                <h2 class="h6 mb-0">Risk timeline</h2>
            </div>
            <div class="table-responsive">
                <table class="table tv-admin-table mb-0">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Signal</th>
                            <th>Points</th>
                            <th>Source</th>
                            <th>Order</th>
                            <th>Notes</th>
                            @if($tvPermissions['update'] ?? false)<th></th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($signals as $signal)
                            <tr>
                                <td class="small">{{ $signal->created_at?->format('d M Y H:i') }}</td>
                                <td>{{ TrustVerificationFraudRiskService::signalLabel($signal->signal_type) }}</td>
                                <td>+{{ $signal->risk_points }}</td>
                                <td>{{ $signal->source }}</td>
                                <td>{{ $signal->order_id ? '#'.$signal->order_id : '—' }}</td>
                                <td class="small">{{ $signal->notes }}</td>
                                @if($tvPermissions['update'] ?? false)
                                    <td class="text-end">
                                        @if($signal->source === 'manual')
                                            <form method="post" action="{{ route('trust-verification.risk.signals.destroy', $signal) }}" class="d-inline"
                                                  onsubmit="return confirm('Remove this manual flag?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link btn-sm text-danger p-0">Remove</button>
                                            </form>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted p-4">No signals recorded. Refresh risk to run detectors.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mb-3">{{ $signals->links() }}</div>

    <div class="tv-admin-card">
        <div class="tv-admin-card__body">
            <h2 class="h6">Recent verification orders</h2>
            <ul class="mb-0">
                @forelse($orders as $o)
                    <li>
                        <a href="{{ route('trust-verification.orders.show', $o) }}">{{ $o->order_number }}</a>
                        — {{ $o->status }} ({{ $o->created_at?->format('d M Y') }})
                    </li>
                @empty
                    <li class="text-muted">No orders</li>
                @endforelse
            </ul>
        </div>
    </div>
</section>
@endsection
