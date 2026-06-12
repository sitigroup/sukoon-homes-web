@extends('layouts.main')
@section('title') Risk signals @endsection
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
            <h1 class="tv-admin-header__title">Risk signals</h1>
            <p class="tv-admin-header__meta mb-0">All fraud signals across customers (internal).</p>
        </div>
    </header>

    <form method="get" class="tv-admin-filter row g-2 mb-3">
        <div class="col-md-2">
            <input type="number" name="customer_id" class="form-control form-control-sm" placeholder="Customer ID"
                   value="{{ $filters['customer_id'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <select name="signal_type" class="form-select form-select-sm">
                <option value="">All types</option>
                @foreach($signalTypes as $type)
                    <option value="{{ $type }}" @selected(($filters['signal_type'] ?? '') === $type)>
                        {{ TrustVerificationFraudRiskService::signalLabel($type) }}
                    </option>
                @endforeach
                <option value="admin_manual_flag" @selected(($filters['signal_type'] ?? '') === 'admin_manual_flag')>Admin manual flag</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="source" class="form-select form-select-sm">
                <option value="">All sources</option>
                <option value="auto" @selected(($filters['source'] ?? '') === 'auto')>Auto</option>
                <option value="manual" @selected(($filters['source'] ?? '') === 'manual')>Manual</option>
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
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Signal</th>
                            <th>Points</th>
                            <th>Source</th>
                            <th>Order</th>
                            <th>Notes</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($signals as $signal)
                            <tr>
                                <td>{{ $signal->id }}</td>
                                <td>
                                    <a href="{{ route('trust-verification.risk.profiles.show', $signal->customer_id) }}">#{{ $signal->customer_id }}</a>
                                </td>
                                <td>{{ TrustVerificationFraudRiskService::signalLabel($signal->signal_type) }}</td>
                                <td>+{{ $signal->risk_points }}</td>
                                <td>{{ $signal->source }}</td>
                                <td>{{ $signal->order_id ?? '—' }}</td>
                                <td class="small">{{ \Illuminate\Support\Str::limit($signal->notes, 60) }}</td>
                                <td class="small">{{ $signal->created_at?->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted p-4">No signals found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-3">{{ $signals->links() }}</div>
</section>
@endsection
