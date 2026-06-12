@extends('layouts.main')

@section('title', __('whatsapp::whatsapp.events_title'))

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
<section class="section">
    @include('whatsapp::admin.whatsapp.partials.nav', ['active' => 'events'])

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    @if(session('whatsapp_feedback'))
        @php $fb = session('whatsapp_feedback'); @endphp
        <div class="alert alert-{{ ($fb['badge'] ?? 'secondary') === 'success' ? 'success' : (($fb['badge'] ?? '') === 'danger' ? 'danger' : 'warning') }}">
            {{ $fb['message'] ?? '' }}
        </div>
    @endif

    <div class="alert alert-light border mb-3">
        <strong>{{ __('whatsapp::whatsapp.self_managed_hint_title') }}</strong>
        {{ __('whatsapp::whatsapp.self_managed_hint_body') }}
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">{{ __('whatsapp::whatsapp.events_title') }}</h5>
            <a href="{{ route('whatsapp.events.create') }}" class="btn btn-sm text-white" style="background:#B89A4A;border-color:#B89A4A;">
                {{ __('whatsapp::whatsapp.add_event') }}
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>{{ __('whatsapp::whatsapp.event_key') }}</th>
                        <th>{{ __('whatsapp::whatsapp.event_type') }}</th>
                        <th>{{ __('whatsapp::whatsapp.display_name') }}</th>
                        <th>{{ __('whatsapp::whatsapp.current_template') }}</th>
                        <th>{{ __('whatsapp::whatsapp.meta_status') }}</th>
                        <th>{{ __('whatsapp::whatsapp.language') }}</th>
                        <th>{{ __('whatsapp::whatsapp.enabled') }}</th>
                        <th>{{ __('whatsapp::whatsapp.platform_trigger') }}</th>
                        <th>{{ __('whatsapp::whatsapp.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($events as $event)
                        @php
                            $tpl = $event->template;
                            $isApproved = $tpl && strtolower((string) $tpl->status) === 'approved';
                            $isSystem = $event->event_type === 'system';
                        @endphp
                        <tr>
                            <td class="font-monospace small">{{ $event->event_key }}</td>
                            <td>
                                <span class="badge {{ $isSystem ? 'bg-dark' : 'bg-info text-dark' }}">
                                    {{ $isSystem ? __('whatsapp::whatsapp.type_system') : __('whatsapp::whatsapp.type_custom') }}
                                </span>
                            </td>
                            <td>{{ $event->display_name ?: '—' }}</td>
                            <td>{{ $tpl?->meta_template_name ?? __('whatsapp::whatsapp.unmapped') }}</td>
                            <td>
                                @if(!$tpl)
                                    <span class="badge bg-secondary">{{ __('whatsapp::whatsapp.unmapped') }}</span>
                                @else
                                    <span class="badge {{ $isApproved ? 'bg-success' : 'bg-warning text-dark' }}">{{ strtoupper((string) ($tpl->status ?: 'pending')) }}</span>
                                @endif
                            </td>
                            <td>{{ $event->language }}</td>
                            <td>
                                <span class="badge {{ $event->enabled ? 'bg-success' : 'bg-secondary' }}">{{ $event->enabled ? __('whatsapp::whatsapp.on') : __('whatsapp::whatsapp.off') }}</span>
                            </td>
                            <td class="small">
                                @if($event->is_wired ?? false)
                                    <span class="text-success">{{ __('whatsapp::whatsapp.trigger_wired') }}</span><br>
                                @else
                                    <span class="text-muted">{{ __('whatsapp::whatsapp.trigger_manual_only') }}</span><br>
                                @endif
                                <span class="text-muted">{{ $event->platform_trigger ?? '' }}</span>
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('whatsapp.events.edit', $event) }}" class="btn btn-sm btn-outline-secondary">{{ __('whatsapp::whatsapp.edit') }}</a>
                                @if(!$isSystem)
                                    <form method="POST" action="{{ route('whatsapp.events.destroy', $event) }}" class="d-inline" onsubmit="return confirm('{{ __('whatsapp::whatsapp.delete_event_confirm') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="confirm_delete" value="1">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('whatsapp::whatsapp.delete') }}</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">{{ __('whatsapp::whatsapp.events_empty') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">{{ __('whatsapp::whatsapp.manual_send_title') }}</h5></div>
        <div class="card-body">
            <p class="text-muted">{{ __('whatsapp::whatsapp.manual_send_help') }}</p>
            <form method="POST" action="{{ route('whatsapp.events.send') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">{{ __('whatsapp::whatsapp.event_key') }}</label>
                    <select name="event_key" class="form-select" required>
                        @foreach($events as $event)
                            @if($event->enabled && $event->template && strtolower((string) $event->template->status) === 'approved')
                                <option value="{{ $event->event_key }}">{{ $event->event_key }} — {{ $event->display_name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('whatsapp::whatsapp.phone') }}</label>
                    <input type="text" name="phone" class="form-control" required placeholder="91XXXXXXXXXX">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('whatsapp::whatsapp.var') }} 1</label>
                    <input type="text" name="var_1" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('whatsapp::whatsapp.var') }} 2</label>
                    <input type="text" name="var_2" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('whatsapp::whatsapp.var') }} 3</label>
                    <input type="text" name="var_3" class="form-control">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn text-white" style="background:#1F2937;border-color:#1F2937;">{{ __('whatsapp::whatsapp.send_template') }}</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
