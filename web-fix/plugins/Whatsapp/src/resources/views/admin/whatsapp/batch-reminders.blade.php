@extends('layouts.main')

@section('title', __('whatsapp::whatsapp.batch_title'))

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
@php
    $tab = session('batch_tab', 'rent');
    $rentPreview = session('rent_preview');
    $renewalPreview = session('renewal_preview');
    $renewalReport = session('renewal_report');
@endphp
<section class="section pt-2">
    @include('whatsapp::admin.whatsapp.partials.nav', ['active' => 'batch'])

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="alert alert-light border small mb-3">
        {{ __('whatsapp::whatsapp.batch_help') }}
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'rent' ? 'active' : '' }}" href="#rent-batch" data-bs-toggle="tab">{{ __('whatsapp::whatsapp.batch_rent_tab') }}</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'renewal' ? 'active' : '' }}" href="#renewal-batch" data-bs-toggle="tab">{{ __('whatsapp::whatsapp.batch_renewal_tab') }}</a>
        </li>
    </ul>

    <div class="tab-content mb-4">
        <div class="tab-pane fade {{ $tab === 'rent' ? 'show active' : '' }}" id="rent-batch">
            @if(! $availability['rent'])
                <div class="alert alert-warning">{{ __('whatsapp::whatsapp.batch_rent_unavailable') }}</div>
            @else
                <div class="card mb-3">
                    <div class="card-body">
                        <form method="POST" action="{{ route('whatsapp.batch-reminders.preview-rent') }}" class="row g-3 align-items-end">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label">{{ __('whatsapp::whatsapp.batch_due_date') }}</label>
                                <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $rentPreview['due_date'] ?? now()->addDays(3)->toDateString()) }}" required>
                            </div>
                            <div class="col-md-8 d-flex gap-2">
                                <button type="submit" class="btn btn-outline-secondary">{{ __('whatsapp::whatsapp.batch_preview') }}</button>
                            </div>
                        </form>
                    </div>
                </div>

                @if(is_array($rentPreview))
                    <div class="card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">{{ __('whatsapp::whatsapp.batch_preview_results') }}</h5>
                            <span class="badge bg-success">{{ __('whatsapp::whatsapp.batch_eligible', ['count' => $rentPreview['eligible'] ?? 0]) }}</span>
                            <span class="badge bg-secondary">{{ __('whatsapp::whatsapp.batch_skipped', ['count' => $rentPreview['skipped'] ?? 0]) }}</span>
                        </div>
                        <div class="card-body p-0">
                            @include('whatsapp::admin.whatsapp.partials.batch-preview-table', ['rows' => $rentPreview['rows'] ?? [], 'type' => 'rent'])
                        </div>
                        @if(($rentPreview['eligible'] ?? 0) > 0)
                            <div class="card-footer">
                                <form method="POST" action="{{ route('whatsapp.batch-reminders.run-rent') }}" onsubmit="return confirm(@json(__('whatsapp::whatsapp.batch_confirm_rent')));">
                                    @csrf
                                    <input type="hidden" name="due_date" value="{{ $rentPreview['due_date'] }}">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="confirm" value="1" id="confirm-rent" required>
                                        <label class="form-check-label" for="confirm-rent">{{ __('whatsapp::whatsapp.batch_confirm_label', ['count' => $rentPreview['eligible']]) }}</label>
                                    </div>
                                    <button type="submit" class="btn text-white" style="background:#1F2937;">{{ __('whatsapp::whatsapp.batch_send_rent') }}</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endif
            @endif
        </div>

        <div class="tab-pane fade {{ $tab === 'renewal' ? 'show active' : '' }}" id="renewal-batch">
            @if(! $availability['renewal'])
                <div class="alert alert-warning">{{ __('whatsapp::whatsapp.batch_renewal_unavailable') }}</div>
            @else
                <div class="card mb-3">
                    <div class="card-body d-flex flex-wrap gap-2 align-items-center">
                        <form method="POST" action="{{ route('whatsapp.batch-reminders.preview-renewal') }}">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary">{{ __('whatsapp::whatsapp.batch_preview') }}</button>
                        </form>
                        @if(is_array($renewalPreview) && isset($renewalPreview['whatsapp_enabled']))
                            @if($renewalPreview['whatsapp_enabled'])
                                <span class="badge bg-success">{{ __('whatsapp::whatsapp.batch_whatsapp_on') }}</span>
                            @else
                                <span class="badge bg-warning text-dark">{{ __('whatsapp::whatsapp.batch_whatsapp_off') }}</span>
                            @endif
                        @endif
                    </div>
                </div>

                @if(is_array($renewalReport))
                    <div class="alert alert-info small">{{ $renewalReport['note'] ?? '' }}</div>
                @endif

                @if(is_array($renewalPreview))
                    <div class="card mb-3">
                        <div class="card-header d-flex flex-wrap gap-2 align-items-center">
                            <h5 class="mb-0">{{ __('whatsapp::whatsapp.batch_preview_results') }}</h5>
                            <span class="badge bg-success">{{ __('whatsapp::whatsapp.batch_eligible', ['count' => $renewalPreview['eligible'] ?? 0]) }}</span>
                            <span class="badge bg-secondary">{{ __('whatsapp::whatsapp.batch_skipped', ['count' => $renewalPreview['skipped'] ?? 0]) }}</span>
                            @if(! empty($renewalPreview['reminder_days']))
                                <span class="text-muted small ms-auto">{{ __('whatsapp::whatsapp.batch_reminder_days', ['days' => implode(', ', $renewalPreview['reminder_days'])]) }}</span>
                            @endif
                        </div>
                        <div class="card-body p-0">
                            @include('whatsapp::admin.whatsapp.partials.batch-preview-table', ['rows' => $renewalPreview['rows'] ?? [], 'type' => 'renewal'])
                        </div>
                        @if(($renewalPreview['eligible'] ?? 0) > 0)
                            <div class="card-footer">
                                <form method="POST" action="{{ route('whatsapp.batch-reminders.run-renewal') }}" onsubmit="return confirm(@json(__('whatsapp::whatsapp.batch_confirm_renewal')));">
                                    @csrf
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="confirm" value="1" id="confirm-renewal" required>
                                        <label class="form-check-label" for="confirm-renewal">{{ __('whatsapp::whatsapp.batch_confirm_renewal_label') }}</label>
                                    </div>
                                    <button type="submit" class="btn text-white" style="background:#1F2937;">{{ __('whatsapp::whatsapp.batch_run_renewal_check') }}</button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    </div>

    @if($batchDetail && $batchDetail->isNotEmpty())
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">{{ __('whatsapp::whatsapp.batch_report') }} — <code>{{ $batchId }}</code></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('whatsapp::whatsapp.batch_recipient') }}</th>
                                <th>{{ __('whatsapp::whatsapp.phone') }}</th>
                                <th>{{ __('whatsapp::whatsapp.event_key') }}</th>
                                <th>{{ __('whatsapp::whatsapp.batch_status') }}</th>
                                <th>{{ __('whatsapp::whatsapp.batch_reason') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($batchDetail as $log)
                                <tr>
                                    <td>{{ $log->label ?: ('#' . $log->recipient_id) }}</td>
                                    <td><code>{{ $log->phone ?: '—' }}</code></td>
                                    <td><code>{{ $log->event_key }}</code></td>
                                    <td>
                                        @if($log->status === 'queued')
                                            <span class="badge bg-success">{{ __('whatsapp::whatsapp.batch_status_queued') }}</span>
                                        @elseif($log->status === 'skipped')
                                            <span class="badge bg-secondary">{{ __('whatsapp::whatsapp.batch_status_skipped') }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ __('whatsapp::whatsapp.batch_status_failed') }}</span>
                                        @endif
                                    </td>
                                    <td class="small text-muted">{{ $log->skip_reason ?: ($log->result_json['message'] ?? '—') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if($recentBatches->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('whatsapp::whatsapp.batch_recent') }}</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('whatsapp::whatsapp.batch_when') }}</th>
                                <th>{{ __('whatsapp::whatsapp.batch_type') }}</th>
                                <th class="text-end">{{ __('whatsapp::whatsapp.batch_status_queued') }}</th>
                                <th class="text-end">{{ __('whatsapp::whatsapp.batch_status_skipped') }}</th>
                                <th class="text-end">{{ __('whatsapp::whatsapp.batch_status_failed') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentBatches as $batch)
                                <tr>
                                    <td>{{ $batch->created_at?->format('d M Y H:i') }}</td>
                                    <td>{{ $batch->batch_type === 'rent' ? __('whatsapp::whatsapp.batch_rent_tab') : __('whatsapp::whatsapp.batch_renewal_tab') }}</td>
                                    <td class="text-end">{{ $batch->sent }}</td>
                                    <td class="text-end">{{ $batch->skipped }}</td>
                                    <td class="text-end">{{ $batch->failed }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('whatsapp.batch-reminders.index', ['batch' => $batch->batch_id]) }}" class="btn btn-sm btn-outline-secondary">{{ __('whatsapp::whatsapp.batch_view_report') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</section>
@endsection
