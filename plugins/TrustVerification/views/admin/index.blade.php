@extends('layouts.main')

@section('title')
    Trust Verification Orders
@endsection

@section('content')
    <section class="section tv-admin tv-admin-page">
        @include('trust-verification::admin.partials.tv-admin-theme')
        @include('trust-verification::admin.partials.nav')

        <header class="tv-admin-header">
            <div>
                <h1 class="tv-admin-header__title">Operations dashboard</h1>
                <p class="tv-admin-header__meta mb-0">Trust Verification orders across all cities</p>
            </div>
        </header>

        <div class="tv-admin-grid tv-admin-grid--kpi mb-3">
            <a href="{{ route('trust-verification.index') }}" class="tv-kpi">
                <div class="tv-kpi__label">Total orders</div>
                <div class="tv-kpi__value">{{ $opsStats['total'] ?? $orders->total() }}</div>
            </a>
            <a href="{{ route('trust-verification.index', ['payment_status' => 'pending']) }}" class="tv-kpi tv-kpi--warning">
                <div class="tv-kpi__label">Pending payment</div>
                <div class="tv-kpi__value">{{ $opsStats['pending_payment'] }}</div>
            </a>
            <a href="{{ route('trust-verification.index', ['status' => 'in_progress']) }}" class="tv-kpi tv-kpi--info">
                <div class="tv-kpi__label">In progress</div>
                <div class="tv-kpi__value">{{ $opsStats['in_progress'] }}</div>
            </a>
            <a href="{{ route('trust-verification.index', ['status' => 'completed']) }}" class="tv-kpi tv-kpi--success">
                <div class="tv-kpi__label">Completed</div>
                <div class="tv-kpi__value">{{ $opsStats['completed'] ?? 0 }}</div>
            </a>
            <a href="{{ route('trust-verification.index', ['overdue' => 1]) }}" class="tv-kpi tv-kpi--danger">
                <div class="tv-kpi__label">Overdue</div>
                <div class="tv-kpi__value">{{ $opsStats['overdue'] }}</div>
            </a>
            <div class="tv-kpi">
                <div class="tv-kpi__label">Completed (7d)</div>
                <div class="tv-kpi__value">{{ $opsStats['completed_week'] }}</div>
            </div>
        </div>

        <div class="tv-admin-card">
            <div class="tv-card__header">
                <h2 class="tv-page-header__title mb-0">Orders</h2>
                <span class="tv-page-header__meta">{{ $orders->total() }} matching</span>
            </div>
            <div class="tv-card__body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @include('trust-verification::admin.partials.quick-filters')

                <form method="get" class="tv-admin-filter row g-2">
                    <div class="col-md-3">
                        <label class="form-label mb-1">Search</label>
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Order #, name, phone"
                               value="{{ $filters['q'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">City</label>
                        <select name="city_slug" class="form-select form-select-sm">
                            <option value="">All cities</option>
                            @foreach($cityOptions as $slug => $label)
                                <option value="{{ $slug }}" @selected(($filters['city_slug'] ?? '') === $slug)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach($statusOptions as $s)
                                <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">Type</label>
                        <select name="order_type" class="form-select form-select-sm">
                            <option value="">All types</option>
                            <option value="tenant" @selected(($filters['order_type'] ?? '') === 'tenant')>Tenant</option>
                            <option value="owner" @selected(($filters['order_type'] ?? '') === 'owner')>Owner</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">Payment</label>
                        <select name="payment_status" class="form-select form-select-sm">
                            <option value="">All</option>
                            @foreach($paymentOptions as $p)
                                <option value="{{ $p }}" @selected(($filters['payment_status'] ?? '') === $p)>{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">Report PDF</label>
                        <select name="has_report" class="form-select form-select-sm">
                            <option value="">Any</option>
                            <option value="1" @selected(($filters['has_report'] ?? '') === '1')>Has PDF</option>
                            <option value="0" @selected(($filters['has_report'] ?? '') === '0')>No PDF</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">From</label>
                        <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">To</label>
                        <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input type="checkbox" name="overdue" value="1" class="form-check-input" id="overdue"
                                   @checked(!empty($filters['overdue']))>
                            <label class="form-check-label" for="overdue">Overdue only</label>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn tv-admin-btn-primary flex-grow-1">Apply filters</button>
                        <a href="{{ route('trust-verification.index') }}" class="btn tv-admin-btn-secondary">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped tv-admin-table align-middle mb-0">
                        <thead>
                        <tr>
                            <th>Order</th>
                            <th>City</th>
                            <th>Type</th>
                            <th>Requester</th>
                            <th>Subject</th>
                            <th>Package</th>
                            <th>Amount</th>
                            <th>Checks</th>
                            <th>Report</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>SLA due</th>
                            <th>Created</th>
                            <th>Updated</th>
                            <th>Overdue</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($orders as $order)
                            @php
                                $dueAt = \App\Plugins\TrustVerification\Services\TrustVerificationOrderTimestampsService::slaDueAt($order);
                                $isOverdue = \App\Plugins\TrustVerification\Services\TrustVerificationOrderTimestampsService::isOverdue($order, $dueAt);
                                $overdueLabel = \App\Plugins\TrustVerification\Services\TrustVerificationOrderTimestampsService::overdueDuration($order, $dueAt);
                                $tvTsFmt = [\App\Plugins\TrustVerification\Services\TrustVerificationOrderTimestampsService::class, 'formatDisplay'];
                                $checks = $order->checkItems;
                                $checksDone = $checks->whereIn('status', ['pass', 'fail'])->count();
                                $checksTotal = $checks->count();
                                $hasPdf = $order->report?->file_path
                                    && \App\Plugins\TrustVerification\Services\TrustVerificationService::reportFileExists($order->report);
                                $statusChip = match ($order->status) {
                                    'completed' => 'tv-admin-chip-success',
                                    'in_progress' => 'tv-admin-chip-warning',
                                    'cancelled' => 'tv-admin-chip-danger',
                                    'submitted' => 'tv-chip-info',
                                    default => 'tv-admin-chip-muted',
                                };
                                $paymentChip = match ($order->payment_status) {
                                    'paid', 'waived' => 'tv-admin-chip-success',
                                    'pending' => 'tv-admin-chip-warning',
                                    default => 'tv-admin-chip-muted',
                                };
                            @endphp
                            <tr class="{{ $isOverdue ? 'table-warning' : '' }}">
                                <td><code>{{ $order->order_number }}</code></td>
                                <td>{{ \App\Plugins\TrustVerification\Services\TrustVerificationService::cityLabel($order->city_slug) }}</td>
                                <td>{{ ucfirst($order->order_type) }}</td>
                                <td>
                                    {{ $order->requester_name ?: '—' }}<br>
                                    <span class="text-muted">{{ $order->requester_phone ?: '' }}</span>
                                </td>
                                <td>
                                    {{ $order->subject?->full_name ?? '—' }}<br>
                                    <span class="text-muted">{{ $order->subject?->phone ?? '' }}</span>
                                </td>
                                <td>{{ $order->package?->name ?? '—' }}</td>
                                <td><span class="tv-price-badge">₹{{ number_format($order->amount) }}</span></td>
                                <td>{{ $checksDone }}/{{ $checksTotal }}</td>
                                <td>
                                    @if($hasPdf)
                                        <span class="tv-admin-chip-success">PDF</span>
                                    @elseif($order->status === 'completed' && in_array($order->payment_status, ['paid', 'waived'], true))
                                        <span class="tv-admin-chip-warning">No PDF</span>
                                    @else
                                        <span class="tv-admin-chip-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="{{ $statusChip }}">{{ $order->status }}</span>
                                    @if($isOverdue)
                                        <span class="tv-admin-chip-danger">overdue</span>
                                    @endif
                                </td>
                                <td><span class="{{ $paymentChip }}">{{ $order->payment_status }}</span></td>
                                <td class="text-nowrap">
                                    @if($dueAt)
                                        @if($isOverdue)
                                            <span class="tv-admin-chip-danger">{{ $tvTsFmt($dueAt) }}</span>
                                        @else
                                            <span class="tv-admin-chip-muted">{{ $tvTsFmt($dueAt) }}</span>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">{{ $order->created_at ? $tvTsFmt($order->created_at) : '—' }}</td>
                                <td class="text-nowrap">{{ $order->updated_at ? $tvTsFmt($order->updated_at) : '—' }}</td>
                                <td>
                                    @if($overdueLabel)
                                        <span class="tv-admin-chip-danger">{{ $overdueLabel }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('trust-verification.orders.show', $order) }}" class="btn btn-sm tv-admin-btn-secondary">Manage</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="16">
                                    <div class="tv-admin-empty-state">
                                        <div class="tv-admin-empty-state__icon"><i class="bi bi-inbox"></i></div>
                                        <p class="mb-0">No orders match your filters</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">{{ $orders->links() }}</div>
            </div>
        </div>
    </section>
@endsection
