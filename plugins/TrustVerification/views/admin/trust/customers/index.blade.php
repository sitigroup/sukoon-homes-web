@extends('layouts.main')
@section('title') Customer trust @endsection
@section('content')
<section class="section tv-admin tv-admin-page">
    @include('trust-verification::admin.partials.tv-admin-theme')
    @include('trust-verification::admin.partials.nav')
    @include('trust-verification::admin.partials.trust-nav')

    <header class="tv-admin-header">
        <div>
            <h1 class="tv-admin-header__title">Customer trust</h1>
            <p class="tv-admin-header__meta mb-0">Scores, badges, breakdown, manual override.</p>
        </div>
        @if($tvPermissions['update'] ?? false)
            <form method="post" action="{{ route('trust-verification.trust.customers.bulk-refresh') }}" class="m-0"
                  onsubmit="return confirm('Recalculate trust for all customers with completed orders?');">
                @csrf
                <button type="submit" class="btn tv-admin-btn-secondary">Bulk refresh</button>
            </form>
        @endif
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <form method="get" class="tv-admin-filter row g-2 mb-3">
        <div class="col-md-4">
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Customer ID" value="{{ $filters['q'] ?? '' }}">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn tv-admin-btn-primary btn-sm w-100">Search</button>
        </div>
    </form>

    <div class="tv-admin-card">
        <div class="tv-admin-card__body--flush p-0">
            <div class="table-responsive">
                <table class="table tv-admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Score</th>
                            <th>Adjustment</th>
                            <th>Badges</th>
                            <th>Public</th>
                            <th>Updated</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($scores as $row)
                            <tr>
                                <td>#{{ $row->customer_id }}</td>
                                <td><strong>{{ $row->trust_score }}</strong> / 100</td>
                                <td>{{ $row->manual_adjustment >= 0 ? '+' : '' }}{{ $row->manual_adjustment }}</td>
                                <td>{{ $badgeCounts[$row->customer_id] ?? 0 }}</td>
                                <td>{{ $row->public_visible ? 'Yes' : 'No' }}</td>
                                <td class="small">{{ $row->last_calculated_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('trust-verification.trust.customers.show', $row->customer_id) }}" class="btn btn-sm tv-admin-btn-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted p-4">No trust scores yet. Complete verifications or run bulk refresh.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-3">{{ $scores->links() }}</div>
</section>
@endsection
