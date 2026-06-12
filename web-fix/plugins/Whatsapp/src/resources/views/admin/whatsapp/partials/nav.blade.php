@php
    $active = $active ?? '';
@endphp
<div class="d-flex flex-wrap gap-2 mb-3">
    @if(function_exists('has_permissions') && has_permissions('inbox', 'whatsapp'))
        <a href="{{ route('whatsapp.inbox.index') }}" class="btn btn-sm {{ $active === 'inbox' ? 'text-white' : 'btn-outline-secondary' }}" @if($active === 'inbox') style="background:#1F2937;border-color:#1F2937;" @endif>{{ __('whatsapp::whatsapp.inbox_title') }}</a>
        <a href="{{ route('whatsapp.delivery.index') }}" class="btn btn-sm {{ $active === 'delivery' ? 'text-white' : 'btn-outline-secondary' }}" @if($active === 'delivery') style="background:#1F2937;border-color:#1F2937;" @endif>{{ __('whatsapp::whatsapp.delivery_title') }}</a>
    @endif
    @if(function_exists('has_permissions') && (has_permissions('events', 'whatsapp') || has_permissions('templates', 'whatsapp')))
        <a href="{{ route('whatsapp.events.index') }}" class="btn btn-sm {{ $active === 'events' ? 'text-white' : 'btn-outline-secondary' }}" @if($active === 'events') style="background:#1F2937;border-color:#1F2937;" @endif>{{ __('whatsapp::whatsapp.events_title') }}</a>
        <a href="{{ route('whatsapp.batch-reminders.index') }}" class="btn btn-sm {{ $active === 'batch' ? 'text-white' : 'btn-outline-secondary' }}" @if($active === 'batch') style="background:#1F2937;border-color:#1F2937;" @endif>{{ __('whatsapp::whatsapp.batch_title') }}</a>
    @endif
    @if(function_exists('has_permissions') && has_permissions('templates', 'whatsapp'))
        <a href="{{ route('whatsapp.templates.index') }}" class="btn btn-sm {{ $active === 'templates' ? 'text-white' : 'btn-outline-secondary' }}" @if($active === 'templates') style="background:#1F2937;border-color:#1F2937;" @endif>{{ __('whatsapp::whatsapp.templates_title') }}</a>
    @endif
    @if(function_exists('has_permissions') && has_permissions('settings', 'whatsapp'))
        <a href="{{ route('whatsapp.canned.index') }}" class="btn btn-sm {{ $active === 'canned' ? 'text-white' : 'btn-outline-secondary' }}" @if($active === 'canned') style="background:#1F2937;border-color:#1F2937;" @endif>{{ __('whatsapp::whatsapp.canned_title') }}</a>
        <a href="{{ route('whatsapp.settings.index') }}" class="btn btn-sm {{ $active === 'settings' ? 'text-white' : 'btn-outline-secondary' }}" @if($active === 'settings') style="background:#1F2937;border-color:#1F2937;" @endif>{{ __('whatsapp::whatsapp.settings_title') }}</a>
    @endif
</div>
