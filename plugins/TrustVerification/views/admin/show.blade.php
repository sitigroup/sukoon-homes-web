@php use Illuminate\Support\Facades\Storage; @endphp
@extends('layouts.main')

@section('title')
    Order {{ $order->order_number }}
@endsection

@section('content')
    @php
        use App\Plugins\TrustVerification\Services\TrustVerificationOrderTimestampsService as TvTs;
        $ts = $orderTimestamps ?? [];
        $fmt = fn ($iso) => $iso
            ? TvTs::formatDisplay(\Carbon\Carbon::parse($iso))
            : 'Not recorded';

        $statusChip = match($order->status) {
            'completed' => 'tv-od-chip--success',
            'in_progress', 'submitted' => 'tv-od-chip--warning',
            'cancelled' => 'tv-od-chip--danger',
            default => 'tv-od-chip--muted',
        };
        $paymentChip = match($order->payment_status) {
            'paid', 'waived' => 'tv-od-chip--success',
            'pending' => 'tv-od-chip--warning',
            default => 'tv-od-chip--muted',
        };
        $verificationPassed = (int) ($checkSummary['pass'] ?? 0);
        $verificationTotal = (int) ($checkSummary['total'] ?? 0);
        $packageName = $order->package?->name ?? '—';
        $orderTypeLabel = ucfirst($order->order_type) . ' verification';
    @endphp

    <section class="section tv-admin tv-admin-page tv-order-detail-page">
        @include('trust-verification::admin.partials.tv-admin-theme')
        @include('trust-verification::admin.partials.tv-order-detail-theme')
        @include('trust-verification::admin.partials.nav')

        <a href="{{ route('trust-verification.index') }}" class="tv-od-link-back">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> All orders
        </a>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="alert tv-alert-privacy mb-3" role="alert">
            <strong>Privacy reminder:</strong> Handle personal documents carefully. Download only when required.
        </div>

        @if($reportMissingForCompleted ?? false)
            <div class="alert alert-warning border-start border-4 mb-3" role="alert">
                <strong>Report file missing:</strong> This order is completed and paid, but no valid PDF is on disk.
                Upload a report below — customers will see “not available yet” until a PDF is uploaded.
            </div>
        @endif

        <div class="tv-od-dashboard">
            <header class="tv-od-summary-wrap">
                <div class="tv-od-summary">
            <div class="tv-od-summary__top">
                <div>
                    <h1 class="tv-od-summary__order">{{ $order->order_number }}</h1>
                    <p class="tv-od-summary__package">{{ $packageName }}</p>
                    <p class="tv-od-summary__meta-line">{{ $orderTypeLabel }} · {{ $cityLabel }}</p>
                </div>
                <div class="text-end">
                    <div class="tv-od-summary__price">₹{{ number_format($order->amount) }}</div>
                </div>
            </div>
            <div class="tv-od-summary__chips">
                <span class="tv-od-chip {{ $statusChip }}">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
                <span class="tv-od-chip {{ $paymentChip }}">{{ ucfirst($order->payment_status) }}</span>
                @if($order->package?->delivery_hours)
                    <span class="tv-od-chip tv-od-chip--muted">SLA {{ $order->package->delivery_hours }}h</span>
                @endif
                @if($dueAt && $dueAt->isPast() && in_array($order->status, ['submitted', 'in_progress']))
                    <span class="tv-od-chip tv-od-chip--danger">Overdue</span>
                @endif
            </div>
            <div class="tv-od-summary__score">
                <span>Verification score:</span>
                <strong>{{ $verificationPassed }} / {{ $verificationTotal }} passed</strong>
                @if($checkSummary['pending'] ?? 0)
                    <span class="text-muted">· {{ $checkSummary['pending'] }} pending</span>
                @endif
            </div>
                </div>
            </header>

            <div class="tv-od-dashboard-body">
                <div class="tv-detail-grid">
                    <div class="tv-od-col tv-od-col--left">
                @include('trust-verification::admin.partials.tv-od-accordion-start', [
                    'icon' => 'bi-person-vcard',
                    'title' => 'Customer & subject',
                    'helper' => 'Requester, subject details, consent',
                    'meta' => $order->subject?->full_name ?: $order->requester_name ?: '—',
                ])
                    <dl class="tv-od-dl">
                        <div class="tv-od-dl__row"><dt>Subject</dt><dd>{{ $order->subject?->full_name ?: '—' }}</dd></div>
                        <div class="tv-od-dl__row"><dt>Contact</dt><dd>
                            {{ $order->subject?->phone ?: '—' }}
                            @if($order->subject?->email)
                                · {{ $order->subject->email }}
                            @endif
                        </dd></div>
                        @if($order->subject?->current_address)
                            <div class="tv-od-dl__row"><dt>Current</dt><dd>{{ $order->subject->current_address }}</dd></div>
                        @endif
                        @if($order->subject?->permanent_address)
                            <div class="tv-od-dl__row"><dt>Permanent</dt><dd>{{ $order->subject->permanent_address }}</dd></div>
                        @endif
                        @if($order->subject?->property_address)
                            <div class="tv-od-dl__row"><dt>Property</dt><dd>{{ $order->subject->property_address }}</dd></div>
                        @endif
                        @if($order->subject?->employment_company)
                            <div class="tv-od-dl__row"><dt>Employment</dt><dd>{{ $order->subject->employment_company }} — {{ $order->subject->employment_role }}</dd></div>
                        @endif
                        @if($order->subject?->id_type)
                            <div class="tv-od-dl__row"><dt>ID</dt><dd>{{ $order->subject->id_type }} — {{ $order->subject->id_number_hint }}</dd></div>
                        @endif
                        <div class="tv-od-dl__row"><dt>Requester</dt><dd>{{ $order->requester_name ?: '—' }}<br>{{ $order->requester_phone ?: '—' }} · {{ $order->requester_email ?: '—' }}</dd></div>
                        @if($order->requester_notes)
                            <div class="tv-od-dl__row"><dt>Notes</dt><dd>{{ $order->requester_notes }}</dd></div>
                        @endif
                        <div class="tv-od-dl__row"><dt>Consent</dt><dd>
                            @if($order->consent_given)
                                Yes
                                @if($order->consent_given_at)
                                    · {{ $order->consent_given_at->format('d M Y H:i') }}
                                @endif
                                @if($order->legal_version)
                                    · v{{ $order->legal_version }}
                                @endif
                            @else
                                Not recorded (legacy)
                            @endif
                        </dd></div>
                        <div class="tv-od-dl__row"><dt>Customer ID</dt><dd>{{ $order->customer_id ?: '—' }}</dd></div>
                        <div class="tv-od-dl__row"><dt>SLA due</dt><dd>{{ $dueAt ? TvTs::formatDisplay($dueAt) : '—' }}
                            @if($dueAt && $dueAt->isPast() && in_array($order->status, ['submitted', 'in_progress']) && ($overdue = TvTs::overdueDuration($order, $dueAt)))
                                <span class="text-danger">({{ $overdue }})</span>
                            @endif
                        </dd></div>
                    </dl>
                @include('trust-verification::admin.partials.tv-od-accordion-end')

                @include('trust-verification::admin.partials.order-documents-automation')

                @include('trust-verification::admin.partials.tv-od-accordion-start', [
                    'icon' => 'bi-clock-history',
                    'title' => 'Timestamps',
                    'helper' => 'Audit trail dates (Asia/Kolkata)',
                    'muted' => true,
                ])
                    <dl class="tv-od-dl">
                        <div class="tv-od-dl__row"><dt>Created</dt><dd>{{ $fmt($ts['created_at'] ?? null) }}</dd></div>
                        <div class="tv-od-dl__row"><dt>Consent</dt><dd>{{ $fmt($ts['consent_given_at'] ?? null) }}</dd></div>
                        <div class="tv-od-dl__row"><dt>Paid</dt><dd>{{ $fmt($ts['paid_at'] ?? null) }}</dd></div>
                        <div class="tv-od-dl__row"><dt>Documents</dt><dd>{{ $fmt($ts['document_uploaded_at'] ?? null) }}</dd></div>
                        <div class="tv-od-dl__row"><dt>Automation</dt><dd>
                            @if(!empty($ts['automation_started_at']))
                                {{ $fmt($ts['automation_started_at']) }}
                                @if(!empty($ts['automation_completed_at']))
                                    → {{ $fmt($ts['automation_completed_at']) }}
                                @else
                                    · In progress
                                @endif
                            @else
                                Not recorded
                            @endif
                        </dd></div>
                        <div class="tv-od-dl__row"><dt>Report</dt><dd>{{ $fmt($ts['report_uploaded_at'] ?? null) }}</dd></div>
                        <div class="tv-od-dl__row"><dt>Review done</dt><dd>{{ $fmt($ts['review_completed_at'] ?? null) }}</dd></div>
                        <div class="tv-od-dl__row"><dt>Updated</dt><dd>{{ $fmt($ts['updated_at'] ?? null) }}</dd></div>
                    </dl>
                @include('trust-verification::admin.partials.tv-od-accordion-end')
            </div>

            <div class="tv-od-col tv-od-col--right">
                @include('trust-verification::admin.partials.tv-od-accordion-start', [
                    'icon' => 'bi-shield-check',
                    'title' => 'Verification checks',
                    'helper' => 'Update pass / fail / pending per check',
                    'meta' => $verificationPassed . ' / ' . $verificationTotal,
                ])
                    @if(!($tvPermissions['update'] ?? false))
                        @include('trust-verification::admin.partials.permission-denied')
                    @else
                        <form method="post" action="{{ route('trust-verification.orders.checks', $order) }}">
                            @csrf
                            <div class="table-responsive">
                                <table class="table tv-od-checks-table mb-3">
                                    <thead><tr><th>Check</th><th>Status</th><th>Notes</th></tr></thead>
                                    <tbody>
                                    @foreach($order->checkItems as $item)
                                        <tr>
                                            <td><strong>{{ $item->label }}</strong></td>
                                            <td>
                                                <select name="checks[{{ $item->id }}][status]" class="form-select tv-od-input">
                                                    @foreach($checkStatuses as $st)
                                                        <option value="{{ $st }}" @selected($item->status === $st)>{{ $st }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="checks[{{ $item->id }}][notes]" class="form-control tv-od-input" value="{{ $item->notes }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <button type="submit" class="btn tv-od-btn-save">Save checks</button>
                        </form>
                    @endif
                @include('trust-verification::admin.partials.tv-od-accordion-end')

                @include('trust-verification::admin.partials.reference-check-panel')
                @include('trust-verification::admin.partials.police-verification-panel')
                @include('trust-verification::admin.partials.verification-badge-order-panel')
                @include('trust-verification::admin.partials.risk-order-panel')

                @if(($tvPermissions['update'] ?? false) || ($tvPermissions['payments'] ?? false))
                    @include('trust-verification::admin.partials.tv-od-accordion-start', [
                        'icon' => 'bi-credit-card',
                        'title' => 'Status & payment',
                        'helper' => 'Order workflow and manual payment',
                        'meta' => ucfirst($order->status) . ' · ' . ucfirst($order->payment_status),
                    ])
                        @if($tvPermissions['update'] ?? false)
                            <form method="post" action="{{ route('trust-verification.orders.status', $order) }}" class="mb-4">
                                @csrf
                                <div class="row tv-od-form-row">
                                    <div class="col-md-6 tv-od-field-group">
                                        <label class="tv-od-label">Order status</label>
                                        <select name="status" class="form-select tv-od-input">
                                            @foreach(['submitted','in_progress','completed','cancelled'] as $s)
                                                <option value="{{ $s }}" @selected($order->status === $s)>{{ $s }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 tv-od-field-group">
                                        <label class="tv-od-label">Admin notes</label>
                                        <textarea name="admin_notes" class="form-control tv-od-input" rows="3">{{ old('admin_notes', $order->admin_notes) }}</textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn tv-od-btn-save">Save status</button>
                                    </div>
                                </div>
                            </form>
                        @else
                            <p class="mb-3"><strong>Order status:</strong> {{ $order->status }}</p>
                            @if($order->admin_notes)
                                <p class="text-muted">{{ $order->admin_notes }}</p>
                            @endif
                        @endif

                        @if($tvPermissions['payments'] ?? false)
                            @if($tvPermissions['update'] ?? false)
                                <hr class="my-4">
                            @endif
                            <form method="post" action="{{ route('trust-verification.orders.payment', $order) }}">
                                @csrf
                                <label class="tv-od-label">Payment (manual v1)</label>
                                <div class="d-flex flex-wrap gap-2 align-items-stretch">
                                    <select name="payment_status" class="form-select tv-od-input flex-grow-1" style="max-width: 280px;">
                                        @foreach(['pending','paid','waived'] as $p)
                                            <option value="{{ $p }}" @selected($order->payment_status === $p)>{{ $p }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn tv-od-btn-secondary">Update payment</button>
                                </div>
                            </form>
                        @elseif($tvPermissions['update'] ?? false)
                            <p class="mb-0 mt-3"><strong>Payment:</strong> {{ $order->payment_status }}</p>
                        @endif
                    @include('trust-verification::admin.partials.tv-od-accordion-end')
                @endif

                @if($tvPermissions['reports'] ?? false)
                    @include('trust-verification::admin.partials.tv-od-accordion-start', [
                        'icon' => 'bi-file-earmark-pdf',
                        'title' => 'Report (PDF)',
                        'helper' => 'Upload verification report for customer',
                        'meta' => $hasReportFile ? 'Uploaded' : ($order->report?->deleted_at ? 'Removed' : 'No file'),
                    ])
                        @if($hasReportFile)
                            <p class="mb-3">
                                <a href="{{ route('trust-verification.orders.report.download', $order) }}" class="btn tv-od-btn-secondary">Download current report</a>
                                @if($order->report?->risk_level)
                                    <span class="ms-2">Risk: <strong>{{ $order->report->risk_level }}</strong></span>
                                @endif
                            </p>
                            <form method="post" action="{{ route('trust-verification.orders.report.delete', $order) }}" class="mb-4"
                                  onsubmit="return confirm('Delete the verification report PDF for this order? The order record, payments, and audit history will be kept.');">
                                @csrf
                                <button type="submit" class="btn tv-od-btn-secondary">Delete report</button>
                            </form>
                        @elseif($order->report)
                            <p class="text-muted mb-3">Report metadata kept; PDF file was removed.
                                @if($order->report->deleted_at)
                                    ({{ $order->report->deleted_at->format('d M Y') }})
                                @endif
                            </p>
                            @if($order->report->risk_level)
                                <p class="mb-3">Risk level: <strong>{{ $order->report->risk_level }}</strong></p>
                            @endif
                        @endif
                        <form method="post" action="{{ route('trust-verification.orders.report', $order) }}" enctype="multipart/form-data">
                            @csrf
                            <div class="row tv-od-form-row">
                                <div class="col-12 tv-od-field-group">
                                    <label class="tv-od-label">Upload PDF</label>
                                    <input type="file" name="report_file" accept="application/pdf" class="form-control tv-od-input">
                                </div>
                                @if(!empty($riskSuggestion['level']))
                                    <div class="col-12">
                                        <div class="alert alert-light border py-2 mb-0">
                                            <strong>Suggested risk:</strong> {{ $riskSuggestion['level'] }}
                                            <span class="text-muted">— {{ $riskSuggestion['reason'] }}</span>
                                        </div>
                                    </div>
                                @elseif(!empty($riskSuggestion['reason']))
                                    <div class="col-12 text-muted">{{ $riskSuggestion['reason'] }}</div>
                                @endif
                                <div class="col-md-6 tv-od-field-group">
                                    <label class="tv-od-label">Risk level</label>
                                    <select name="risk_level" class="form-select tv-od-input">
                                        <option value="">Auto (from checks)</option>
                                        @foreach($riskLevels as $r)
                                            <option value="{{ $r }}" @selected($order->report?->risk_level === $r || (empty($order->report?->risk_level) && ($riskSuggestion['level'] ?? '') === $r))>{{ $r }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 tv-od-field-group">
                                    <label class="tv-od-label">Summary</label>
                                    <textarea name="summary" class="form-control tv-od-input" rows="4" placeholder="Auto-generated summary available if left blank">{{ old('summary', $order->report?->summary ?: $riskSummaryDraft) }}</textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn tv-od-btn-save">Save report</button>
                                </div>
                            </div>
                        </form>
                    @include('trust-verification::admin.partials.tv-od-accordion-end')
                @endif
            </div>
                </div>

        @if($tvPermissions['audit'] ?? false)
            <div class="tv-od-audit-section">
            @include('trust-verification::admin.partials.tv-od-accordion-start', [
                'icon' => 'bi-journal-text',
                'title' => 'Audit history',
                'helper' => 'System events for this order',
                'meta' => $auditLogs->count() . ' events',
                'muted' => true,
            ])
                @if($auditLogs->isEmpty())
                    <p class="text-muted mb-0">No audit events recorded yet.</p>
                @else
                    <div class="table-responsive">
                        <table class="table tv-od-checks-table mb-0">
                            <thead>
                                <tr>
                                    <th>Date / time</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($auditLogs as $log)
                                    <tr>
                                        <td class="text-nowrap">{{ $log->created_at?->format('d M Y H:i') ?? '—' }}</td>
                                        <td>{{ $log->actorLabel() }}</td>
                                        <td><code>{{ $log->action }}</code></td>
                                        <td>{{ $log->description ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @include('trust-verification::admin.partials.tv-od-accordion-end')
            </div>
        @endif
            </div>
        </div>
    </section>
@endsection
