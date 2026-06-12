@php
    $accIcon = $icon ?? 'bi-folder2';
    $accTitle = $title ?? '';
    $accHelper = $helper ?? '';
    $accMeta = $meta ?? '';
    $accOpen = !empty($open);
    $accMuted = !empty($muted);
@endphp
<details class="tv-od-acc {{ $accMuted ? 'tv-od-acc--muted' : '' }}"{!! $accOpen ? ' open' : '' !!}>
    <summary>
        <span class="tv-od-acc__icon"><i class="bi {{ $accIcon }}" aria-hidden="true"></i></span>
        <span>
            <span class="tv-od-acc__title">{{ $accTitle }}</span>
            @if(($accHelper ?? '') !== '')
                <span class="tv-od-acc__helper">{{ $accHelper }}</span>
            @endif
        </span>
        @if(($accMeta ?? '') !== '')
            <span class="tv-od-acc__meta">{{ $accMeta }}</span>
        @endif
        <span class="tv-od-acc__chevron" aria-hidden="true"></span>
    </summary>
    <div class="tv-od-acc__body">
