@extends('layouts.main')

@section('title', __('whatsapp::whatsapp.settings_title'))

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
    @include('whatsapp::admin.whatsapp.partials.nav', ['active' => 'settings'])
    @php
        $hasAccessToken = !empty($settings?->access_token);
        $hasAppSecret = !empty($settings?->app_secret);
        $masked = '••••••••••';
    @endphp
    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h5 class="mb-0">{{ __('whatsapp::whatsapp.settings_title') }}</h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge {{ ($settings->webhook_verified ?? false) ? 'bg-success' : 'bg-secondary' }}">
                    {{ ($settings->webhook_verified ?? false) ? __('whatsapp::whatsapp.webhook_verified') : __('whatsapp::whatsapp.webhook_pending') }}
                </span>
                <form method="POST" action="{{ route('whatsapp.settings.test') }}" class="m-0">
                    @csrf
                    <button class="btn btn-sm text-white" style="background:#1F2937;border-color:#1F2937;">{{ __('whatsapp::whatsapp.test_connection') }}</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('whatsapp.settings.store') }}" class="row g-3">
                @csrf
                <div class="col-12">
                    <p class="text-muted mb-1">
                        {{ __('whatsapp::whatsapp.settings_help') }}
                    </p>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">{{ __('whatsapp::whatsapp.meta_app_id') }}</label>
                    <input name="meta_app_id" class="form-control" value="{{ old('meta_app_id', $settings->meta_app_id ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">{{ __('whatsapp::whatsapp.waba_id') }}</label>
                    <input name="waba_id" class="form-control" value="{{ old('waba_id', $settings->waba_id ?? '') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">{{ __('whatsapp::whatsapp.phone_number_id') }}</label>
                    <input name="phone_number_id" class="form-control" value="{{ old('phone_number_id', $settings->phone_number_id ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('whatsapp::whatsapp.access_token') }}</label>
                    @if($hasAccessToken)
                        <input type="text" class="form-control mb-2" value="{{ $masked }}" disabled>
                    @endif
                    <input type="password" name="access_token" class="form-control" placeholder="{{ __('whatsapp::whatsapp.secret_placeholder') }}">
                    @if($hasAccessToken)
                        <small class="text-muted d-block mt-1">{{ __('whatsapp::whatsapp.saved_value') }}: {{ $masked }}</small>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('whatsapp::whatsapp.app_secret') }}</label>
                    @if($hasAppSecret)
                        <input type="text" class="form-control mb-2" value="{{ $masked }}" disabled>
                    @endif
                    <input type="password" name="app_secret" class="form-control" placeholder="{{ __('whatsapp::whatsapp.secret_placeholder') }}">
                    @if($hasAppSecret)
                        <small class="text-muted d-block mt-1">{{ __('whatsapp::whatsapp.saved_value') }}: {{ $masked }}</small>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('whatsapp::whatsapp.verify_token') }}</label>
                    <input name="verify_token" class="form-control" value="{{ old('verify_token', $settings->verify_token ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('whatsapp::whatsapp.environment') }}</label>
                    <select name="environment" class="form-select">
                        @foreach(['test_number','sandbox_waba','production_waba'] as $mode)
                            <option value="{{ $mode }}" @selected(old('environment', old('environment_mode', $settings->environment_mode ?? 'test_number')) === $mode)>{{ $mode }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12"><hr class="my-2"></div>
                <div class="col-12">
                    <h6 class="fw-semibold mb-1">{{ __('whatsapp::whatsapp.off_hours_title') }}</h6>
                    <p class="text-muted small mb-0">{{ __('whatsapp::whatsapp.off_hours_help') }}</p>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="off_hours_enabled" id="offHoursEnabled" value="1"
                            {{ old('off_hours_enabled', $settings->off_hours_enabled ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="offHoursEnabled">{{ __('whatsapp::whatsapp.off_hours_enabled') }}</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">{{ __('whatsapp::whatsapp.business_hours_start') }}</label>
                    <input type="time" name="business_hours_start" class="form-control"
                        value="{{ old('business_hours_start', $settings->business_hours_start ?? '09:00') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">{{ __('whatsapp::whatsapp.business_hours_end') }}</label>
                    <input type="time" name="business_hours_end" class="form-control"
                        value="{{ old('business_hours_end', $settings->business_hours_end ?? '18:00') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ __('whatsapp::whatsapp.business_timezone') }}</label>
                    <input name="business_timezone" class="form-control"
                        value="{{ old('business_timezone', $settings->business_timezone ?? 'Asia/Kolkata') }}">
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">{{ __('whatsapp::whatsapp.off_hours_reply_text') }}</label>
                    <textarea name="off_hours_reply_text" class="form-control" rows="3" maxlength="1000"
                        placeholder="{{ __('whatsapp::whatsapp.off_hours_reply_placeholder') }}">{{ old('off_hours_reply_text', $settings->off_hours_reply_text ?? '') }}</textarea>
                </div>

                <div class="col-12"><hr class="my-2"></div>
                <div class="col-12">
                    <h6 class="fw-semibold mb-1">{{ __('whatsapp::whatsapp.keyword_title') }}</h6>
                    <p class="text-muted small mb-0">{{ __('whatsapp::whatsapp.keyword_help') }}</p>
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="keyword_automation_enabled" id="keywordAutomationEnabled" value="1"
                            {{ old('keyword_automation_enabled', $settings->keyword_automation_enabled ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="keywordAutomationEnabled">{{ __('whatsapp::whatsapp.keyword_enabled') }}</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">{{ __('whatsapp::whatsapp.keyword_reply_repair') }}</label>
                    <textarea name="keyword_reply_repair" class="form-control" rows="2" maxlength="1000"
                        placeholder="{{ __('whatsapp::whatsapp.keyword_reply_repair_placeholder') }}">{{ old('keyword_reply_repair', $settings->keyword_reply_repair ?? '') }}</textarea>
                    <div class="form-text">{{ __('whatsapp::whatsapp.keyword_triggers_repair') }}</div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">{{ __('whatsapp::whatsapp.keyword_reply_rent') }}</label>
                    <textarea name="keyword_reply_rent" class="form-control" rows="2" maxlength="1000"
                        placeholder="{{ __('whatsapp::whatsapp.keyword_reply_rent_placeholder') }}">{{ old('keyword_reply_rent', $settings->keyword_reply_rent ?? '') }}</textarea>
                    <div class="form-text">{{ __('whatsapp::whatsapp.keyword_triggers_rent') }}</div>
                </div>
                <div class="col-12">
                    <label class="form-label fw-semibold">{{ __('whatsapp::whatsapp.keyword_reply_agreement') }}</label>
                    <textarea name="keyword_reply_agreement" class="form-control" rows="2" maxlength="1000"
                        placeholder="{{ __('whatsapp::whatsapp.keyword_reply_agreement_placeholder') }}">{{ old('keyword_reply_agreement', $settings->keyword_reply_agreement ?? '') }}</textarea>
                    <div class="form-text">{{ __('whatsapp::whatsapp.keyword_triggers_agreement') }}</div>
                </div>

                <div class="col-12 d-flex gap-2 flex-wrap align-items-center pt-2">
                    <button type="submit" class="btn px-4 text-white" style="background:#1F2937;border-color:#1F2937;">
                        {{ __('whatsapp::whatsapp.save') }}
                    </button>
                    <small class="text-muted">{{ __('whatsapp::whatsapp.save_hint') }}</small>
                </div>
            </form>
        </div>
    </div>

    @php $health = $webhookHealth ?? []; @endphp
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">{{ __('whatsapp::whatsapp.webhook_health_title') }}</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold small text-muted">{{ __('whatsapp::whatsapp.webhook_url') }}</label>
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" readonly value="{{ $health['webhook_url'] ?? url('/api/whatsapp/webhook') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted">{{ __('whatsapp::whatsapp.webhook_last_received') }}</label>
                    <p class="mb-0">
                        @if(!empty($health['last_webhook_at']))
                            {{ \Carbon\Carbon::parse($health['last_webhook_at'])->format('d M Y H:i') }}
                            <span class="badge {{ ($health['is_recent_webhook'] ?? false) ? 'bg-success' : 'bg-warning' }} ms-1">
                                {{ ($health['is_recent_webhook'] ?? false) ? __('whatsapp::whatsapp.webhook_recent_ok') : __('whatsapp::whatsapp.webhook_recent_stale') }}
                            </span>
                        @else
                            <span class="text-muted">{{ __('whatsapp::whatsapp.webhook_never') }}</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted">{{ __('whatsapp::whatsapp.webhook_inbound_24h') }}</label>
                    <p class="mb-0 fw-semibold">{{ (int) ($health['inbound_24h'] ?? 0) }}</p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted">{{ __('whatsapp::whatsapp.webhook_pending_jobs') }}</label>
                    <p class="mb-0">{{ (int) ($health['pending_jobs'] ?? 0) }}</p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold small text-muted">{{ __('whatsapp::whatsapp.webhook_failed_jobs_24h') }}</label>
                    <p class="mb-0 {{ ($health['failed_jobs_24h'] ?? 0) > 0 ? 'text-danger fw-semibold' : '' }}">{{ (int) ($health['failed_jobs_24h'] ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">{{ __('whatsapp::whatsapp.production_checklist_title') }}</h5>
        </div>
        <div class="card-body">
            <ul class="mb-0 small">
                <li>{{ __('whatsapp::whatsapp.production_check_1') }}</li>
                <li>{{ __('whatsapp::whatsapp.production_check_2') }}</li>
                <li>{{ __('whatsapp::whatsapp.production_check_3') }}</li>
                <li>{{ __('whatsapp::whatsapp.production_check_4') }}</li>
                <li>{{ __('whatsapp::whatsapp.production_check_5') }}</li>
            </ul>
        </div>
    </div>
</section>
@endsection

