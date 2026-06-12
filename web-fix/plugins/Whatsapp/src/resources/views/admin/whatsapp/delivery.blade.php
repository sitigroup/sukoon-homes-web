@extends('layouts.main')

@section('title', __('whatsapp::whatsapp.delivery_title'))

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
<section class="section pt-2">
    @include('whatsapp::admin.whatsapp.partials.nav', ['active' => 'delivery'])

    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-3">
            <span class="text-muted small">{{ __('whatsapp::whatsapp.delivery_period') }}: {{ __('whatsapp::whatsapp.delivery_last_days', ['days' => $days]) }}</span>
            <form method="GET" class="d-flex gap-2 m-0">
                <select name="days" class="form-select form-select-sm" style="width:auto;">
                    @foreach([7, 14, 30] as $d)
                        <option value="{{ $d }}" @selected($days === $d)>{{ $d }} {{ __('whatsapp::whatsapp.days') }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm text-white" style="background:#1F2937;">{{ __('whatsapp::whatsapp.apply') }}</button>
            </form>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">{{ __('whatsapp::whatsapp.delivery_summary') }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('whatsapp::whatsapp.event_key') }}</th>
                            <th class="text-end">{{ __('whatsapp::whatsapp.delivery_sent') }}</th>
                            <th class="text-end">{{ __('whatsapp::whatsapp.delivery_delivered') }}</th>
                            <th class="text-end">{{ __('whatsapp::whatsapp.delivery_read') }}</th>
                            <th class="text-end text-danger">{{ __('whatsapp::whatsapp.delivery_failed') }}</th>
                            <th class="text-end">{{ __('whatsapp::whatsapp.delivery_total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byEvent as $row)
                            <tr>
                                <td><code>{{ $row['event_key'] }}</code></td>
                                <td class="text-end">{{ $row['sent'] }}</td>
                                <td class="text-end">{{ $row['delivered'] }}</td>
                                <td class="text-end">{{ $row['read'] }}</td>
                                <td class="text-end text-danger fw-semibold">{{ $row['failed'] }}</td>
                                <td class="text-end">{{ $row['total'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-muted text-center py-4">{{ __('whatsapp::whatsapp.delivery_no_data') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ __('whatsapp::whatsapp.delivery_recent_failures') }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('whatsapp::whatsapp.delivery_when') }}</th>
                            <th>{{ __('whatsapp::whatsapp.phone') }}</th>
                            <th>{{ __('whatsapp::whatsapp.event_key') }}</th>
                            <th>{{ __('whatsapp::whatsapp.delivery_preview') }}</th>
                            <th>{{ __('whatsapp::whatsapp.meta_error') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentFailures as $f)
                            <tr>
                                <td class="text-nowrap small">{{ $f['at'] }}</td>
                                <td class="small">{{ $f['phone'] ?: '-' }}</td>
                                <td class="small"><code>{{ $f['template_key'] ?: '-' }}</code></td>
                                <td class="small" style="max-width:220px;">{{ Str::limit($f['preview'], 80) }}</td>
                                <td class="small text-danger">{{ $f['error'] ?: __('whatsapp::whatsapp.delivery_unknown_error') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted text-center py-4">{{ __('whatsapp::whatsapp.delivery_no_failures') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
