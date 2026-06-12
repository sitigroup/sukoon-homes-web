@extends('layouts.main')
@section('title'){{ __('Renewal Reminder Settings') }}@endsection
@section('page-title')
<div class="page-title">
    <div class="row">
        <div class="col-12 col-md-6 order-md-1 order-last">
            <h4>@yield('title')</h4>
            <p class="text-subtitle text-muted">{{ __('Configure reminder schedule and rent increase logic') }}</p>
        </div>
        <div class="col-12 col-md-6 order-md-2 order-first">
            <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ url('home') }}">{{ __('Dashboard') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.renewal-reminders.dashboard') }}">{{ __('Renewals') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('Settings') }}</li>
                </ol>
            </nav>
        </div>
    </div>
</div>
@endsection
@section('content')
<section class="section">
    @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger alert-dismissible fade show"><button type="button" class="btn-close" data-bs-dismiss="alert"></button>{{ $errors->first() }}</div>@endif

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.renewal-reminders.settings.update') }}">
                @csrf

                <div class="mb-4">
                    <label class="form-label fw-semibold">{{ __('Reminder Days') }}</label>
                    <div class="d-flex gap-3 flex-wrap">
                        @php $availableDays = [7, 14, 15, 30, 45, 60, 90]; @endphp
                        @foreach($availableDays as $day)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="reminder_days[]" value="{{ $day }}" id="day{{ $day }}"
                                    {{ in_array($day, old('reminder_days', $settings->reminder_days ?? []), true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="day{{ $day }}">{{ $day }} {{ __('days') }}</label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">{{ __('Rent Increase Type') }}</label>
                        <select name="rent_increase_type" class="form-select" required>
                            @foreach(['percentage' => 'Percentage', 'fixed' => 'Fixed', 'none' => 'None'] as $type => $label)
                                <option value="{{ $type }}" {{ old('rent_increase_type', $settings->rent_increase_type) === $type ? 'selected' : '' }}>
                                    {{ __($label) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">{{ __('Rent Increase Value') }}</label>
                        <input type="number" class="form-control" name="rent_increase_value" min="0" step="0.01"
                               value="{{ old('rent_increase_value', $settings->rent_increase_value) }}" required>
                    </div>
                </div>

                <div class="mb-4 d-flex gap-4 flex-wrap">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="send_email" id="sendEmail" value="1"
                            {{ old('send_email', $settings->send_email) ? 'checked' : '' }}>
                        <label class="form-check-label" for="sendEmail">{{ __('Send email') }}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="send_push" id="sendPush" value="1"
                            {{ old('send_push', $settings->send_push) ? 'checked' : '' }}>
                        <label class="form-check-label" for="sendPush">{{ __('Send push') }}</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="send_whatsapp" id="sendWhatsapp" value="1"
                            {{ old('send_whatsapp', $settings->send_whatsapp) ? 'checked' : '' }}>
                        <label class="form-check-label" for="sendWhatsapp">{{ __('Send WhatsApp') }}</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Save Settings') }}</button>
            </form>
        </div>
    </div>
</section>
@endsection
