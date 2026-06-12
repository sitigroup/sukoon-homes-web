@extends('layouts.main')
@section('title'){{ __('Owner Tenancies') }}@endsection
@section('page-title')
<div class="page-title">
    <div class="row">
        <div class="col-12 col-md-6 order-md-1 order-last">
            <h4>@yield('title')</h4>
            <p class="text-subtitle text-muted">{{ __('Link owners to tenants, properties and agreements; control dashboard access') }}</p>
        </div>
        <div class="col-12 col-md-6 order-md-2 order-first">
            <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('home') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('Owner Tenancies') }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>
@endsection
@section('content')
@php
    $badge = [
        'pending'   => 'secondary',
        'active'    => 'success',
        'notice'    => 'warning',
        'move_out'  => 'info',
        'completed' => 'dark',
        'cancelled' => 'danger',
    ];
    $statuses = ['pending','active','notice','move_out','completed','cancelled'];
@endphp
<section class="section">

    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('error') }}</div>@endif
    @if(session('whatsapp_feedback'))
        @php
            $wf = session('whatsapp_feedback');
            $wfBadge = $wf['badge'] ?? 'secondary';
            $wfMessage = $wf['message'] ?? __('No result');
        @endphp
        <div class="alert alert-light border d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-{{ $wfBadge }}">{{ __('WhatsApp') }}</span>
                <span class="small fw-semibold">{{ $wfMessage }}</span>
            </div>
            @if(($wf['status'] ?? '') === 'fallback' && !empty($wf['fallback_wa_me_url']))
                <a href="{{ $wf['fallback_wa_me_url'] }}" target="_blank" class="btn btn-sm text-white" style="background:#1F2937;border-color:#1F2937;">
                    {{ __('Open wa.me fallback') }}
                </a>
            @endif
        </div>
    @endif

    @if(($conflictSummary['count'] ?? 0) > 0)
    <div class="alert alert-warning d-flex align-items-start">
        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
        <div>
            <strong>{{ $conflictSummary['count'] }} {{ trans_choice('tenancy needs|tenancies need', $conflictSummary['count']) }} {{ __('attention') }}.</strong>
            <span class="ms-1">{{ __('Conflicts on') }}:
                {!! collect($conflictSummary['ids'])->map(fn($id) => '<a href="#row-'.$id.'" class="alert-link">#'.$id.'</a>')->implode(', ') !!}.
            </span>
            <div class="small text-muted mt-1">{{ __('Review the owner assignment and property owner pointer for the highlighted rows below.') }}</div>
        </div>
    </div>
    @endif

    {{-- Stats --}}
    <div class="row mb-4">
        <div class="col-6 col-md mb-3"><div class="card"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold" style="font-size:11px;">{{ __('Total') }}</div><div class="fw-bold" style="font-size:1.6rem;">{{ $stats['total'] }}</div></div></div></div>
        <div class="col-6 col-md mb-3"><div class="card border-start border-success border-3"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold" style="font-size:11px;">{{ __('Active') }}</div><div class="fw-bold text-success" style="font-size:1.6rem;">{{ $stats['active'] }}</div></div></div></div>
        <div class="col-6 col-md mb-3"><div class="card border-start border-secondary border-3"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold" style="font-size:11px;">{{ __('Pending') }}</div><div class="fw-bold" style="font-size:1.6rem;">{{ $stats['pending'] }}</div></div></div></div>
        <div class="col-6 col-md mb-3"><div class="card border-start border-3" style="border-color:#B89A4A !important;"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold" style="font-size:11px;">{{ __('Pending owner (not registered)') }}</div><div class="fw-bold" style="font-size:1.6rem;color:#92400e;">{{ $stats['pending_owner'] ?? 0 }}</div></div></div></div>
        <div class="col-6 col-md mb-3"><div class="card border-start border-warning border-3"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold" style="font-size:11px;">{{ __('Notice / Move-out') }}</div><div class="fw-bold text-warning" style="font-size:1.6rem;">{{ $stats['notice'] }}</div></div></div></div>
        <div class="col-6 col-md mb-3"><div class="card"><div class="card-body py-3"><div class="text-muted small text-uppercase fw-semibold" style="font-size:11px;">{{ __('Completed') }}</div><div class="fw-bold" style="font-size:1.6rem;">{{ $stats['completed'] }}</div></div></div></div>
    </div>

    {{-- Assign owner to property + create tenancy --}}
    <div class="row mb-3">
        <div class="col-md-7 mb-3">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">{{ __('Assign Owner to Property') }}</h5></div>
                <div class="card-body">
                    <p class="text-muted small">{{ __('Links a registered customer as the property owner (shortcut pointer used by the owner dashboard).') }}</p>
                    <form method="POST" action="{{ route('admin.owner-tenancies.assign-owner') }}" class="row g-2">
                        @csrf
                        <div class="col-md-5">
                            <select name="property_id" class="form-select" required>
                                <option value="">{{ __('Select property') }}</option>
                                @foreach($properties as $p)
                                <option value="{{ $p->id }}">{{ $p->title }} (#{{ $p->id }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <select name="owner_customer_id" class="form-select" required>
                                <option value="">{{ __('Select owner') }}</option>
                                @foreach($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name ?? ('#'.$c->id) }} ({{ $c->mobile ?? '—' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-link-45deg"></i> {{ __('Assign') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-5 mb-3">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0">{{ __('New Tenancy') }}</h5></div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <p class="text-muted small">{{ __('Create a full owner tenancy (owner + tenant + property + agreement).') }}</p>
                    <a href="{{ route('admin.owner-assignment-requests.index') }}" class="btn btn-outline-secondary position-relative me-2 mb-2" style="border-color:#1F2937;color:#1F2937;font-weight:600;">
                        <i class="bi bi-inbox"></i> {{ __('Owner Requests') }}
                        @php
                            $pendingOwnerReq = 0;
                            if (\Illuminate\Support\Facades\Schema::hasTable('owner_assignment_requests')) {
                                $pendingOwnerReq = \App\Plugins\AreaManagement\Models\OwnerAssignmentRequest::where('status', 'pending')->count();
                            }
                        @endphp
                        @if($pendingOwnerReq > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-dark" style="background:#B89A4A;">{{ $pendingOwnerReq }}</span>
                        @endif
                    </a>
                    <a href="{{ route('admin.owner-tenancies.create') }}" class="btn btn-primary"><i class="bi bi-plus-circle"></i> {{ __('Create Owner Tenancy') }}</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Tenancies table --}}
    <div class="card">
        <div class="card-header"><h5 class="mb-0">{{ __('All Owner Tenancies') }}</h5></div>
        <div class="card-body p-0">
            @if($tenancies->isEmpty())
            <div class="text-center py-5 text-muted"><i class="bi bi-people" style="font-size:2.5rem;"></i><p class="mt-2">{{ __('No owner tenancies yet.') }}</p></div>
            @else
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>{{ __('Owner') }}</th>
                            <th>{{ __('Tenant') }}</th>
                            <th>{{ __('Property') }}</th>
                            <th>{{ __('Agreement') }}</th>
                            <th>{{ __('Flow') }}</th>
                        <th>{{ __('Status') }}</th>
                            <th>{{ __('Dashboard') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tenancies as $t)
                        <tr id="row-{{ $t->id }}" @class(['table-warning' => $t->owner_conflict])>
                            <td>{{ $t->id }}</td>
<td>
                                @if($t->isPendingOwner())
                                    <span class="badge rounded-pill text-dark mb-1" style="background:#B89A4A;">{{ __('Owner not registered') }}</span>
                                    <div class="fw-semibold">{{ $t->pendingOwnerLabel() }}</div>
                                    @if($t->owner_phone)
                                        <div class="small text-muted">{{ $t->owner_phone }}</div>
                                    @endif
                                    @if($t->owner_email)
                                        <div class="small text-muted">{{ $t->owner_email }}</div>
                                    @endif
                                @else
                                    {{ $t->owner?->name ?? ('#'.$t->owner_customer_id) }}
                                @endif
                                @if(!empty($t->conflict_reasons))
                                <div class="mt-1">
                                    @foreach($t->conflict_reasons as $reason)
                                    <span class="badge bg-warning text-dark d-inline-block mb-1" title="{{ $reason }}"><i class="bi bi-exclamation-triangle"></i> {{ $reason }}</span>
                                    @endforeach
                                </div>
                                @endif
                            </td>
                            <td>{{ $t->agreement?->tenant_name ?? $t->tenant?->name ?? '—' }}</td>
                            <td>{{ $t->property?->title ?? ('#'.$t->property_id) }}</td>
                            <td>
                                @php $tRef = app(\App\Plugins\Maintenance\Services\MaintenanceTenancyDisplayService::class)->forOwnerTenancy($t); @endphp
                                @if($tRef['kyc_only'] || $t->agreement)
                                <span class="badge rounded-pill" style="background:#B89A4A;color:#1F2937;font-weight:600;">{{ $tRef['display'] }}</span>
                                @if($tRef['kyc_only'])
                                <span class="badge rounded-pill ms-1" style="background:#E5E7EB;color:#1F2937;font-size:10px;font-weight:600;">{{ __('KYC-only') }}</span>
                                @elseif($t->agreement)
                                <div class="small text-muted mt-1">{{ ucwords(str_replace('_', ' ', $t->agreement->status)) }}</div>
                                @endif
                                @else <span class="text-muted">—</span> @endif
                            </td>
                            <td>
                                <span class="badge rounded-pill" style="background:#E5E7EB;color:#1F2937;font-size:11px;font-weight:600;">
                                    {{ $t->flowTypeLabel() }}
                                </span>
                            </td>
                            <td><span class="badge bg-{{ $badge[$t->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_',' ',$t->status)) }}</span></td>
                            <td>
                                @php $closed = in_array($t->status, ['completed','cancelled'], true); @endphp
                                @if($closed)
                                <span class="badge bg-secondary-subtle text-secondary border" title="{{ __('Tenancy is closed — hidden from owner regardless of this toggle') }}">{{ __('Hidden (closed)') }}</span>
                                @elseif($t->dashboard_enabled)
                                <span class="badge bg-success-subtle text-success border border-success">{{ __('Enabled') }}</span>
                                @else
                                <span class="badge bg-danger-subtle text-danger border border-danger">{{ __('Frozen') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    @if($t->isPendingOwner())
                                    <button class="btn btn-sm text-white" style="background:#1F2937;" data-bs-toggle="modal" data-bs-target="#linkOwnerModal{{ $t->id }}" title="{{ __('Link Owner') }}">
                                        <i class="bi bi-person-check"></i> {{ __('Link Owner') }}
                                    </button>
                                    @endif
                                    <a href="{{ route('admin.owner-tenancies.edit', $t) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('Edit') }}"><i class="bi bi-pencil"></i></a>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#statusModal{{ $t->id }}" title="{{ __('Change status') }}"><i class="bi bi-arrow-left-right"></i></button>
                                    <form method="POST" action="{{ route('admin.owner-tenancies.toggle-dashboard', $t) }}" onsubmit="return confirm('{{ $t->dashboard_enabled ? __('Freeze the owner dashboard for tenancy #:id? The owner will lose access until re-enabled.', ['id' => $t->id]) : __('Re-enable the owner dashboard for tenancy #:id?', ['id' => $t->id]) }}');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-{{ $t->dashboard_enabled ? 'danger' : 'success' }}" title="{{ $t->dashboard_enabled ? __('Freeze dashboard') : __('Enable dashboard') }}">
                                            <i class="bi bi-{{ $t->dashboard_enabled ? 'lock' : 'unlock' }}"></i> {{ $t->dashboard_enabled ? __('Freeze') : __('Enable') }}
                                        </button>
                                    </form>
                                    <button class="btn btn-sm text-white" style="background:#1F2937;" data-bs-toggle="modal" data-bs-target="#rentReminderModal{{ $t->id }}" title="{{ __('Send Rent Reminder') }}">
                                        <i class="bi bi-bell"></i> {{ __('Rent Reminder') }}
                                    </button>
                                </div>
                            </td>
                        </tr>


                        @if($t->isPendingOwner())
                        <div class="modal fade" id="linkOwnerModal{{ $t->id }}" tabindex="-1">
                            <div class="modal-dialog"><div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ __('Link Owner') }} — #{{ $t->id }}</h5>
                                    <button class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('admin.owner-tenancies.link-owner', $t) }}">
                                    @csrf
                                    <div class="modal-body">
                                        <p class="small text-muted">{{ __('Pending owner on agreement:') }} <strong>{{ $t->owner_phone ?? '—' }}</strong>@if($t->owner_email) · {{ $t->owner_email }}@endif</p>
                                        <label class="form-label small fw-semibold">{{ __('Registered customer') }}</label>
                                        <select name="owner_customer_id" class="form-select" required>
                                            <option value="">{{ __('Select customer') }}</option>
                                            @foreach($customers as $c)
                                            <option value="{{ $c->id }}">{{ $c->name ?? ('#'.$c->id) }} ({{ $c->mobile ?? '—' }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                        <button type="submit" class="btn text-white" style="background:#1F2937;">{{ __('Link Owner') }}</button>
                                    </div>
                                </form>
                            </div></div>
                        </div>
                        @endif

                        {{-- Status change modal --}}
                        <div class="modal fade" id="statusModal{{ $t->id }}" tabindex="-1">
                            <div class="modal-dialog"><div class="modal-content">
                                <div class="modal-header"><h5 class="modal-title">{{ __('Change Status') }} — #{{ $t->id }}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                                <form method="POST" action="{{ route('admin.owner-tenancies.status', $t) }}">
                                    @csrf
                                    <div class="modal-body">
                                        <p class="text-muted small">{{ __('Manual override. This is audited. Automatic transitions will continue to apply.') }}</p>
                                        <select name="status" class="form-select">
                                            @foreach($statuses as $s)
                                            <option value="{{ $s }}" @selected($t->status === $s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                        <button type="submit" class="btn btn-primary">{{ __('Update Status') }}</button>
                                    </div>
                                </form>
                            </div></div>
                        </div>

                        <div class="modal fade" id="rentReminderModal{{ $t->id }}" tabindex="-1">
                            <div class="modal-dialog"><div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">{{ __('Send Rent Reminder') }} — #{{ $t->id }}</h5>
                                    <button class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form method="POST" action="{{ route('admin.owner-tenancies.rent-reminder', $t) }}">
                                    @csrf
                                    <div class="modal-body">
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">{{ __('Rent Amount') }}</label>
                                            <input type="number" step="0.01" min="0" name="rent_amount" class="form-control" value="{{ (float) ($t->monthly_rent ?? 0) }}">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small fw-semibold">{{ __('Due Date') }}</label>
                                            <input type="date" name="due_date" class="form-control" value="{{ now()->toDateString() }}" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                        <button type="submit" class="btn text-white" style="background:#1F2937;">{{ __('Send') }}</button>
                                    </div>
                                </form>
                            </div></div>
                        </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

</section>
@endsection
