@extends('layouts.main')

@section('title', __('whatsapp::whatsapp.templates_title'))

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first"></div>
        </div>
    </div>
@endsection

@section('content')
<section class="section pt-2">
    @include('whatsapp::admin.whatsapp.partials.nav', ['active' => 'templates'])
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">{{ __('whatsapp::whatsapp.templates_title') }}</h5>
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('whatsapp.events.index') }}" class="btn btn-sm text-white" style="background:#B89A4A;border-color:#B89A4A;">{{ __('whatsapp::whatsapp.manage_events') }}</a>
                <form method="POST" action="{{ route('whatsapp.templates.sync') }}" class="m-0">
                    @csrf
                    <button class="btn btn-sm text-white" style="background:#1F2937;border-color:#1F2937;">{{ __('whatsapp::whatsapp.sync_templates') }}</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                {{ __('Sync approved Utility/Authentication templates from Meta, map them to internal events, then enable what should be used.') }}
            </p>
            <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>{{ __('whatsapp::whatsapp.template_name') }}</th>
                    <th>{{ __('whatsapp::whatsapp.language') }}</th>
                    <th>{{ __('whatsapp::whatsapp.category') }}</th>
                    <th>{{ __('whatsapp::whatsapp.meta_status') }}</th>
                    <th style="min-width:320px;">{{ __('whatsapp::whatsapp.internal_event') }}</th>
                    <th>{{ __('whatsapp::whatsapp.enabled') }}</th>
                    <th>{{ __('whatsapp::whatsapp.action') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($templates as $t)
                    <tr>
                        <td class="fw-semibold">{{ $t->meta_template_name }}</td>
                        <td><span class="badge bg-secondary">{{ $t->language }}</span></td>
                        <td>{{ $t->category }}</td>
                        <td>
                            @php $isApproved = strtolower((string) $t->status) === 'approved'; @endphp
                            <span class="badge {{ $isApproved ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ strtoupper((string) ($t->status ?: 'pending')) }}
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('whatsapp.templates.map', $t->id) }}" class="d-flex gap-2 flex-wrap flex-lg-nowrap align-items-center">
                                @csrf
                                @php $mapped = $mapByTemplate->get($t->id); @endphp
                                <select name="internal_key" class="form-select form-select-sm">
                                    <option value="__none__">{{ __('whatsapp::whatsapp.unmapped') }}</option>
                                    @foreach($eventOptions as $eventKey)
                                        <option value="{{ $eventKey }}" @selected(($mapped->event_key ?? null) === $eventKey)>{{ $eventKey }}</option>
                                    @endforeach
                                </select>
                                <select name="language" class="form-select form-select-sm">
                                    <option value="en" @selected(($mapped->language ?? $t->language) === 'en')>en</option>
                                    <option value="hi" @selected(($mapped->language ?? $t->language) === 'hi')>hi</option>
                                </select>
                                <button class="btn btn-sm text-white" style="background:#1F2937;border-color:#1F2937;">{{ __('whatsapp::whatsapp.save') }}</button>
                            </form>
                        </td>
                        <td>
                            <span class="badge {{ $t->enabled ? 'bg-success' : 'bg-secondary' }}">{{ $t->enabled ? 'Yes' : 'No' }}</span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('whatsapp.templates.toggle', $t->id) }}" class="m-0">
                                @csrf
                                <button class="btn btn-sm px-3 text-white" style="background:#1F2937;border-color:#1F2937;">Toggle</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            @if($templates->isEmpty())
                <div class="alert alert-warning mt-3 mb-0">
                    {{ __('whatsapp::whatsapp.templates_empty') }}
                </div>
            @endif
            <div class="pt-3">{{ $templates->links() }}</div>
        </div>
    </div>
</section>
@endsection

