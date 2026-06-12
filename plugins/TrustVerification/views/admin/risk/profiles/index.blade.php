@extends('layouts.main')
@section('title') Risk profiles @endsection
@section('content')
<section class="section tv-admin tv-admin-page">
    @include('trust-verification::admin.partials.tv-admin-theme')
    @include('trust-verification::admin.partials.nav')
    @include('trust-verification::admin.partials.risk-nav')

    <header class="tv-admin-header">
        <div>
            <h1 class="tv-admin-header__title">Risk profiles</h1>
            <p class="tv-admin-header__meta mb-0">Internal fraud risk only — never shown on Homes or public APIs.</p>
        </div>
        @if($tvPermissions['update'] ?? false)
            <form method="post" action="{{ route('trust-verification.risk.profiles.bulk-refresh') }}" class="m-0"
                  onsubmit="return confirm('Recalculate risk for all customers with verification orders?');">
                @csrf
                <button type="submit" class="btn tv-admin-btn-secondary">Nightly-style bulk refresh</button>
            </form>
        @endif
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <form method="get" class="tv-admin-filter row g-2 mb-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label small mb-1">Customer ID</label>
            <input type="text" name="q" class="form-control form-control-sm" value="{{ $filters['q'] ?? '' }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Level</label>
            <select name="level" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach($levels as $lvl)
                    <option value="{{ $lvl }}" @selected(($filters['level'] ?? '') === $lvl)>{{ ucfirst($lvl) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 d-flex flex-wrap gap-3">
            <label class="form-check small mb-0">
                <input type="checkbox" name="duplicate_phone" value="1" class="form-check-input" @checked($filters['duplicate_phone'] ?? false)>
                Duplicate phone
            </label>
            <label class="form-check small mb-0">
                <input type="checkbox" name="rejected_police" value="1" class="form-check-input" @checked($filters['rejected_police'] ?? false)>
                Rejected police
            </label>
            <label class="form-check small mb-0">
                <input type="checkbox" name="failed_reference" value="1" class="form-check-input" @checked($filters['failed_reference'] ?? false)>
                Failed reference
            </label>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn tv-admin-btn-primary btn-sm w-100">Filter</button>
        </div>
    </form>

    <div class="tv-admin-card">
        <div class="tv-admin-card__body--flush p-0">
            <div class="table-responsive">
                <table class="table tv-admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Trust score</th>
                            <th>Risk score</th>
                            <th>Signals</th>
                            <th>Level</th>
                            <th>Manual notes</th>
                            <th>Updated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($profiles as $row)
                            <tr>
                                <td>#{{ $row->customer_id }}</td>
                                <td>{{ $trustScores[$row->customer_id] ?? '—' }}</td>
                                <td><strong>{{ $row->risk_score }}</strong> / 100</td>
                                <td>{{ $signalCounts[$row->customer_id] ?? 0 }}</td>
                                <td>
                                    <span class="badge bg-{{ \App\Plugins\TrustVerification\Services\TrustVerificationFraudRiskService::levelBadgeClass($row->risk_level) }}">
                                        {{ ucfirst($row->risk_level) }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ \Illuminate\Support\Str::limit($row->notes, 40) ?: '—' }}</td>
                                <td class="small">{{ $row->last_calculated_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('trust-verification.risk.profiles.show', $row->customer_id) }}" class="btn btn-sm tv-admin-btn-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted p-4">No risk profiles yet. Run bulk refresh or complete verifications.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-3">{{ $profiles->links() }}</div>
</section>
@endsection
