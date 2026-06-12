@extends('layouts.main')
@section('title') Verification Badges @endsection
@section('content')
<section class="section tv-admin tv-admin-page">
    @include('trust-verification::admin.partials.tv-admin-theme')
    @include('trust-verification::admin.partials.nav')

    <header class="tv-admin-header">
        <div>
            <h1 class="tv-admin-header__title">Verification Badges</h1>
            <p class="tv-admin-header__meta mb-0">Issued Sukoon Verified Owner / Tenant badges (SVO / SVT numbers).</p>
        </div>
    </header>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <form method="get" class="tv-admin-filter row g-2 mb-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label small mb-1">Search</label>
            <input type="text" name="q" class="form-control form-control-sm" placeholder="Badge #, customer ID, order #"
                   value="{{ $filters['q'] ?? '' }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Type</label>
            <select name="badge_type" class="form-select form-select-sm">
                <option value="">All</option>
                <option value="owner" @selected(($filters['badge_type'] ?? '') === 'owner')>Owner (SVO)</option>
                <option value="tenant" @selected(($filters['badge_type'] ?? '') === 'tenant')>Tenant (SVT)</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All</option>
                @foreach($statuses as $st)
                    <option value="{{ $st }}" @selected(($filters['status'] ?? '') === $st)>{{ ucfirst($st) }}</option>
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
                            <th>Badge #</th>
                            <th>Type</th>
                            <th>Customer</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Issued</th>
                            <th>Expires</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td><code>{{ $row->badge_number }}</code></td>
                                <td>{{ $row->badge_type === 'owner' ? 'Sukoon Verified Owner' : 'Sukoon Verified Tenant' }}</td>
                                <td>#{{ $row->customer_id }}</td>
                                <td>
                                    @if($row->order)
                                        <a href="{{ route('trust-verification.orders.show', $row->order_id) }}">{{ $row->order->order_number }}</a>
                                    @else
                                        #{{ $row->order_id }}
                                    @endif
                                </td>
                                <td>{{ ucfirst($row->status) }}</td>
                                <td class="small">{{ $row->issued_at?->format('d M Y') ?? '—' }}</td>
                                <td class="small">{{ $row->expires_at?->format('d M Y') ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('trust-verification.verification-badges.show', $row) }}" class="btn btn-sm tv-admin-btn-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted p-4">No verification badges issued yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-3">{{ $rows->links() }}</div>
</section>
@endsection
