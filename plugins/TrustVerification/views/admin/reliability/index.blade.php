@extends('layouts.main')
@section('title') Tenant Reliability @endsection
@section('content')
<section class="section tv-admin tv-admin-page">
    @include('trust-verification::admin.partials.tv-admin-theme')
    @include('trust-verification::admin.partials.nav')

    <header class="tv-admin-header">
        <div>
            <h1 class="tv-admin-header__title">Tenant Reliability</h1>
            <p class="tv-admin-header__meta mb-0">Owner-safe summary only — no fraud risk, PII, or score breakdown.</p>
        </div>
        @if($tvPermissions['update'] ?? false)
            <form method="post" action="{{ route('trust-verification.reliability.bulk-refresh') }}" class="m-0"
                  onsubmit="return confirm('Recalculate tenant reliability for all completed tenant orders?');">
                @csrf
                <button type="submit" class="btn tv-admin-btn-secondary">Bulk refresh</button>
            </form>
        @endif
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <form method="get" class="tv-admin-filter row g-2 mb-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label small mb-1">Customer ID</label>
            <input type="text" name="q" class="form-control form-control-sm" value="{{ $filters['q'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small mb-1">Level</label>
            <select name="level" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach($levels as $slug => $label)
                    <option value="{{ $slug }}" @selected(($filters['level'] ?? '') === $slug)>{{ $label }}</option>
                @endforeach
            </select>
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
                            <th>Reliability level</th>
                            <th>Verification %</th>
                            <th>Status</th>
                            <th>Visibility</th>
                            <th>Updated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>#{{ $row->customer_id }}</td>
                                <td>{{ $levels[$row->reliability_level] ?? $row->reliability_level }}</td>
                                <td><strong>{{ $row->verification_completion }}%</strong></td>
                                <td>{{ ucfirst($row->verification_status) }}</td>
                                <td>{{ $row->public_visible ? 'Public' : 'Hidden' }}</td>
                                <td class="small">{{ $row->last_calculated_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('trust-verification.reliability.show', $row->customer_id) }}" class="btn btn-sm tv-admin-btn-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted p-4">No tenant reliability rows yet. Complete tenant verifications or run bulk refresh.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-3">{{ $rows->links() }}</div>
</section>
@endsection
