@extends('layouts.main')

@section('title', __('whatsapp::whatsapp.inbox_title'))

@section('page-title')
    <div class="page-title d-none d-lg-block">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
@php
    use App\Plugins\Whatsapp\Support\WaInboxUiHelper;
    use App\Plugins\Whatsapp\Support\WhatsappConversationTagCatalog;
    use App\Plugins\Whatsapp\Support\WhatsappInboxAgentHelper;
    $activeId = $conversation?->id;
    $tagFilter = $tagFilter ?? '';
    $activeTags = $activeTags ?? [];
    $inboxQuery = static function (array $extra = []) use ($search, $filter, $activeId, $tagFilter): array {
        return array_filter(array_merge([
            'conversation_id' => $activeId ?: null,
            'search' => $search ?: null,
            'filter' => ($filter ?? 'all') !== 'all' ? $filter : null,
            'tag' => $tagFilter !== '' ? $tagFilter : null,
        ], $extra));
    };
@endphp
<style>
    :root {
        --wa-graphite: #1F2937;
        --wa-gold: #B89A4A;
        --wa-green: #25D366;
        --wa-bg: #f0f2f5;
        --wa-in-bg: #ffffff;
        --wa-out-bg: #1F2937;
        --wa-muted: #6B7280;
        --wa-border: #e5e7eb;
    }
    .wa-inbox-app {
        display: flex;
        height: calc(100vh - 220px);
        min-height: 520px;
        max-height: 820px;
        background: var(--wa-bg);
        border: 1px solid var(--wa-border);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(31, 41, 55, 0.08);
    }
    .wa-inbox-sidebar {
        width: 100%;
        max-width: 360px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        background: #fff;
        border-right: 1px solid var(--wa-border);
    }
    .wa-inbox-sidebar-head {
        padding: 1rem 1rem 0.75rem;
        background: var(--wa-graphite);
        color: #fff;
    }
    .wa-inbox-sidebar-head h5 { color: #fff; font-size: 1rem; margin: 0; font-weight: 600; }
    .wa-inbox-search {
        margin-top: 0.75rem;
        border: none;
        border-radius: 8px;
        padding: 0.55rem 0.85rem;
        font-size: 0.875rem;
        background: rgba(255,255,255,0.12);
        color: #fff;
    }
    .wa-inbox-search::placeholder { color: rgba(255,255,255,0.65); }
    .wa-inbox-search:focus {
        background: #fff;
        color: var(--wa-graphite);
        box-shadow: 0 0 0 2px var(--wa-gold);
        outline: none;
    }
    .wa-inbox-list {
        flex: 1;
        overflow-y: auto;
    }
    .wa-conv-row {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        text-decoration: none;
        color: inherit;
        border-bottom: 1px solid var(--wa-border);
        transition: background 0.15s ease;
    }
    .wa-conv-row:hover { background: #f9fafb; }
    .wa-conv-row.is-active {
        background: #f3f4f6;
        border-left: 3px solid var(--wa-gold);
        padding-left: calc(1rem - 3px);
    }
    .wa-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: var(--wa-graphite);
        color: #fff;
        font-size: 0.8rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .wa-conv-body { min-width: 0; flex: 1; }
    .wa-conv-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.2rem;
    }
    .wa-conv-name {
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--wa-graphite);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .wa-conv-time { font-size: 0.7rem; color: var(--wa-muted); flex-shrink: 0; }
    .wa-conv-preview {
        font-size: 0.8rem;
        color: var(--wa-muted);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin: 0;
    }
    .wa-conv-meta {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        margin-top: 0.35rem;
    }
    .wa-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .wa-status--open { background: #22c55e; }
    .wa-status--pending { background: var(--wa-gold); }
    .wa-status--resolved { background: #9ca3af; }
    .wa-status-label {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--wa-muted);
        font-weight: 600;
    }
    .wa-unread {
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        border-radius: 9px;
        background: var(--wa-green);
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-left: auto;
    }
    .wa-inbox-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
        background: #efeae2;
        background-image: radial-gradient(circle at 1px 1px, rgba(0,0,0,0.04) 1px, transparent 0);
        background-size: 18px 18px;
    }
    .wa-inbox-main--empty {
        align-items: center;
        justify-content: center;
        color: var(--wa-muted);
    }
    .wa-chat-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        background: #fff;
        border-bottom: 1px solid var(--wa-border);
        flex-wrap: wrap;
    }
    .wa-chat-header-info { min-width: 0; flex: 1; }
    .wa-chat-header-name { font-weight: 600; font-size: 1rem; color: var(--wa-graphite); margin: 0; }
    .wa-chat-header-sub { font-size: 0.75rem; color: var(--wa-muted); margin: 0; }
    .wa-chat-header-actions { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
    .wa-inbox-back {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        color: var(--wa-graphite);
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 0.35rem 0.5rem;
        border-radius: 6px;
    }
    .wa-inbox-back:hover { background: #f3f4f6; color: var(--wa-graphite); }
    .wa-thread {
        flex: 1;
        overflow-y: auto;
        padding: 1rem 1.25rem;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }
    .wa-date-sep {
        align-self: center;
        font-size: 0.7rem;
        color: var(--wa-muted);
        background: rgba(255,255,255,0.9);
        padding: 0.25rem 0.75rem;
        border-radius: 8px;
        margin: 0.5rem 0;
        box-shadow: 0 1px 2px rgba(0,0,0,0.06);
    }
    .wa-bubble-row {
        display: flex;
        max-width: 78%;
    }
    .wa-bubble-row--in { align-self: flex-start; }
    .wa-bubble-row--out { align-self: flex-end; }
    .wa-bubble {
        padding: 0.5rem 0.7rem 0.35rem;
        border-radius: 8px;
        box-shadow: 0 1px 1px rgba(0,0,0,0.08);
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    .wa-bubble-row--in .wa-bubble {
        background: var(--wa-in-bg);
        border-top-left-radius: 2px;
    }
    .wa-bubble-row--out .wa-bubble {
        background: var(--wa-out-bg);
        color: #fff;
        border-top-right-radius: 2px;
    }
    .wa-bubble--failed {
        background: #fef2f2 !important;
        color: #991b1b !important;
        border: 1px solid #fecaca;
    }
    .wa-bubble-type {
        display: inline-block;
        font-size: 0.6rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        padding: 0.1rem 0.35rem;
        border-radius: 4px;
        margin-bottom: 0.35rem;
    }
    .wa-bubble-row--in .wa-bubble-type {
        background: #e0e7ff;
        color: #3730a3;
    }
    .wa-bubble-row--out .wa-bubble-type {
        background: rgba(255,255,255,0.2);
        color: rgba(255,255,255,0.95);
    }
    .wa-bubble-text { font-size: 0.875rem; line-height: 1.45; margin: 0; }
    .wa-bubble-media {
        max-width: 220px;
        max-height: 220px;
        border-radius: 8px;
        display: block;
        margin-bottom: 0.35rem;
        object-fit: cover;
    }
    .wa-bubble-doc {
        font-size: 0.875rem;
        text-decoration: underline;
        display: inline-block;
        margin-bottom: 0.35rem;
    }
    .wa-bubble-foot {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.35rem;
        margin-top: 0.25rem;
    }
    .wa-bubble-time { font-size: 0.65rem; opacity: 0.75; }
    .wa-bubble-row--out .wa-bubble-time { color: rgba(255,255,255,0.85); }
    .wa-bubble-row--in .wa-bubble-time { color: var(--wa-muted); }
    .wa-delivery {
        display: inline-flex;
        align-items: center;
        gap: 2px;
        line-height: 1;
    }
    .wa-delivery__tick {
        display: inline-block;
        width: 5px;
        height: 9px;
        border-right: 2px solid currentColor;
        border-bottom: 2px solid currentColor;
        transform: rotate(45deg);
        margin-left: 1px;
        opacity: 0.85;
    }
    .wa-delivery--sent { color: rgba(255,255,255,0.7); }
    .wa-delivery--delivered { color: rgba(255,255,255,0.85); }
    .wa-delivery--read { color: #93c5fd; }
    .wa-delivery--failed { color: #fca5a5; font-size: 0.65rem; font-weight: 600; }
    .wa-bubble-error {
        font-size: 0.72rem;
        color: #b91c1c;
        margin-top: 0.4rem;
        padding-top: 0.35rem;
        border-top: 1px solid #fecaca;
    }
    .wa-bubble-error-title { font-weight: 600; display: block; margin-bottom: 0.15rem; }
    .wa-composer {
        background: #f0f2f5;
        border-top: 1px solid var(--wa-border);
        padding: 0.75rem 1rem 1rem;
    }
    .wa-composer-reply {
        display: flex;
        gap: 0.5rem;
        align-items: flex-end;
        background: #fff;
        border-radius: 10px;
        padding: 0.5rem;
        border: 1px solid var(--wa-border);
    }
    .wa-composer-reply textarea {
        border: none;
        resize: none;
        flex: 1;
        font-size: 0.9rem;
        min-height: 42px;
        max-height: 120px;
        box-shadow: none !important;
    }
    .wa-composer-reply textarea:focus { outline: none; }
    .wa-btn-send {
        background: var(--wa-graphite);
        border: 1px solid var(--wa-graphite);
        color: #fff;
        font-weight: 600;
        padding: 0.5rem 1.1rem;
        border-radius: 8px;
        white-space: nowrap;
    }
    .wa-btn-send:hover, .wa-btn-send:focus {
        background: #111827;
        border-color: #111827;
        color: #fff;
    }
    .wa-btn-gold {
        background: var(--wa-gold);
        border: 1px solid var(--wa-gold);
        color: #fff;
        font-weight: 600;
    }
    .wa-btn-gold:hover { background: #a6883f; border-color: #a6883f; color: #fff; }
    .wa-quick-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin-bottom: 0.5rem;
        align-items: center;
    }
    .wa-quick-bar-label {
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--wa-muted);
        margin-right: 0.25rem;
    }
    .wa-quick-chip {
        font-size: 0.72rem;
        padding: 0.25rem 0.55rem;
        border-radius: 999px;
        border: 1px solid var(--wa-gold);
        background: #fff;
        color: var(--wa-graphite);
        font-weight: 600;
        cursor: pointer;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .wa-quick-chip:hover { background: #faf8f3; }
    .wa-quick-link {
        font-size: 0.72rem;
        padding: 0.25rem 0.55rem;
        border-radius: 6px;
        border: 1px solid var(--wa-border);
        background: #fff;
        color: var(--wa-graphite);
        text-decoration: none;
        font-weight: 600;
    }
    .wa-quick-link:hover { border-color: var(--wa-gold); color: var(--wa-gold); }
    .wa-notes-panel {
        border-top: 2px solid #fde68a;
        background: #fffbeb;
    }
    .wa-notes-badge {
        font-size: 0.6rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 700;
        color: #92400e;
        background: #fde68a;
        padding: 0.15rem 0.4rem;
        border-radius: 4px;
    }
    .wa-note-item {
        background: #fff;
        border: 1px solid #fde68a;
        border-radius: 8px;
        padding: 0.5rem 0.65rem;
        margin-bottom: 0.5rem;
        font-size: 0.8rem;
    }
    .wa-note-meta {
        font-size: 0.68rem;
        color: var(--wa-muted);
        margin-bottom: 0.25rem;
    }
    .wa-note-body {
        color: var(--wa-graphite);
        margin: 0;
        white-space: pre-wrap;
        word-break: break-word;
    }
    .wa-note-form textarea {
        font-size: 0.8rem;
        min-height: 56px;
        resize: vertical;
    }
    .wa-inbox-event-panel {
        margin-top: 0.65rem;
        background: #fff;
        border: 1px solid var(--wa-border);
        border-radius: 10px;
        overflow: hidden;
    }
    .wa-inbox-event-panel summary {
        padding: 0.6rem 0.85rem;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--wa-graphite);
        cursor: pointer;
        list-style: none;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .wa-inbox-event-panel summary::-webkit-details-marker { display: none; }
    .wa-inbox-event-panel summary::after {
        content: '+';
        font-weight: 400;
        color: var(--wa-gold);
        font-size: 1.1rem;
    }
    .wa-inbox-event-panel[open] summary::after { content: '−'; }
    .wa-inbox-event-body { padding: 0 0.85rem 0.85rem; border-top: 1px solid var(--wa-border); }
    .wa-event-vars-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    .wa-event-vars-info {
        font-size: 0.75rem;
        color: var(--wa-muted);
        margin-top: 0.5rem;
    }
    .wa-inbox-alerts { margin-bottom: 1rem; }
    .wa-inbox-pagination {
        padding: 0.5rem 1rem;
        border-top: 1px solid var(--wa-border);
        font-size: 0.8rem;
    }
    .wa-filter-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin-top: 0.65rem;
    }
    .wa-filter-chip {
        font-size: 0.7rem;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        text-decoration: none;
        border: 1px solid rgba(255,255,255,0.35);
        color: rgba(255,255,255,0.9);
    }
    .wa-filter-chip.is-active {
        background: var(--wa-gold);
        border-color: var(--wa-gold);
        color: #fff;
        font-weight: 600;
    }
    .wa-tag-filter-chips {
        max-height: 72px;
        overflow-y: auto;
        margin-top: 0.4rem;
    }
    .wa-tag-pill {
        display: inline-block;
        font-size: 0.58rem;
        font-weight: 600;
        padding: 0.1rem 0.35rem;
        border-radius: 4px;
        background: rgba(184, 154, 74, 0.2);
        color: #92400e;
        margin-right: 0.2rem;
        margin-top: 0.2rem;
    }
    .wa-tags-editor {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem 0.65rem;
        margin-bottom: 0.5rem;
    }
    .wa-tags-editor label {
        font-size: 0.72rem;
        color: var(--wa-graphite);
        margin: 0;
        cursor: pointer;
    }
    .wa-assignee-label {
        font-size: 0.62rem;
        color: var(--wa-gold);
        font-weight: 600;
        max-width: 72px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .wa-assign-form {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        align-items: center;
    }
    .wa-inbox-context {
        width: 280px;
        flex-shrink: 0;
        background: #fff;
        border-left: 1px solid var(--wa-border);
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }
    .wa-context-head {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--wa-border);
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--wa-graphite);
    }
    .wa-context-section {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--wa-border);
    }
    .wa-context-section h6 {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--wa-muted);
        margin-bottom: 0.5rem;
    }
    .wa-context-placeholder {
        font-size: 0.8rem;
        color: var(--wa-muted);
        margin: 0;
    }
    .wa-context-value {
        font-size: 0.85rem;
        color: var(--wa-graphite);
        margin: 0 0 0.35rem;
        font-weight: 600;
    }
    .wa-context-meta {
        font-size: 0.75rem;
        color: var(--wa-muted);
        margin: 0 0 0.5rem;
    }
    .wa-context-links {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }
    .wa-context-link {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--wa-gold);
        text-decoration: none;
    }
    .wa-context-link:hover { color: #a6883f; text-decoration: underline; }
    @keyframes wa-shimmer {
        0% { background-position: -200px 0; }
        100% { background-position: 200px 0; }
    }
    .wa-skeleton-line {
        height: 12px;
        border-radius: 4px;
        margin-bottom: 8px;
        background: linear-gradient(90deg, #f3f4f6 0px, #e5e7eb 40px, #f3f4f6 80px);
        background-size: 200px 100%;
        animation: wa-shimmer 1.2s ease-in-out infinite;
    }
    @media (max-width: 1199.98px) {
        .wa-inbox-context { display: none; }
    }
    @media (max-width: 991.98px) {
        .wa-inbox-app { height: calc(100vh - 160px); min-height: 420px; }
        .wa-inbox-sidebar { max-width: none; }
        .wa-inbox-app.has-chat .wa-inbox-sidebar { display: none; }
        .wa-inbox-app:not(.has-chat) .wa-inbox-main { display: none; }
        .wa-bubble-row { max-width: 92%; }
    }
</style>

<section class="section pt-2">
    @include('whatsapp::admin.whatsapp.partials.nav', ['active' => 'inbox'])

    <div class="wa-inbox-alerts">
        @if(session('success')) <div class="alert alert-success mb-2">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="alert alert-danger mb-2">{{ session('error') }}</div> @endif
        @if(session('whatsapp_feedback'))
            @php $fb = session('whatsapp_feedback'); @endphp
            <div class="alert alert-{{ ($fb['badge'] ?? 'secondary') === 'success' ? 'success' : (($fb['badge'] ?? '') === 'danger' ? 'danger' : 'warning') }} mb-2">
                {{ $fb['message'] ?? '' }}
            </div>
        @endif
    </div>

    <div class="wa-inbox-app {{ $conversation ? 'has-chat' : '' }}">
        <aside class="wa-inbox-sidebar">
            <div class="wa-inbox-sidebar-head">
                <h5>{{ __('whatsapp::whatsapp.inbox_title') }}</h5>
                <form method="GET" action="{{ route('whatsapp.inbox.index') }}">
                    @if($activeId)
                        <input type="hidden" name="conversation_id" value="{{ $activeId }}">
                    @endif
                    @if(!empty($filter) && $filter !== 'all')
                        <input type="hidden" name="filter" value="{{ $filter }}">
                    @endif
                    @if($tagFilter !== '')
                        <input type="hidden" name="tag" value="{{ $tagFilter }}">
                    @endif
                    <input
                        class="form-control wa-inbox-search w-100"
                        name="search"
                        value="{{ $search }}"
                        placeholder="{{ __('whatsapp::whatsapp.search_conversations') }}"
                        autocomplete="off"
                    >
                </form>
                @php $currentFilter = $filter ?? 'all'; @endphp
                <div class="wa-filter-chips">
                    @foreach(['all', 'mine', 'open', 'pending', 'resolved', 'unassigned'] as $chip)
                        <a href="{{ route('whatsapp.inbox.index', $inboxQuery(['filter' => $chip === 'all' ? null : $chip])) }}"
                           class="wa-filter-chip {{ $currentFilter === $chip ? 'is-active' : '' }}">{{ __('whatsapp::whatsapp.filter_' . $chip) }}</a>
                    @endforeach
                </div>
                <div class="wa-filter-chips wa-tag-filter-chips">
                    <a href="{{ route('whatsapp.inbox.index', $inboxQuery(['tag' => null])) }}"
                       class="wa-filter-chip {{ $tagFilter === '' ? 'is-active' : '' }}">{{ __('whatsapp::whatsapp.filter_tags_all') }}</a>
                    @foreach(WhatsappConversationTagCatalog::keys() as $tagKey)
                        <a href="{{ route('whatsapp.inbox.index', $inboxQuery(['tag' => $tagKey])) }}"
                           class="wa-filter-chip {{ $tagFilter === $tagKey ? 'is-active' : '' }}">{{ WhatsappConversationTagCatalog::label($tagKey) }}</a>
                    @endforeach
                </div>
            </div>
            <div class="wa-inbox-list">
                @forelse($conversations as $c)
                    @php
                        $contact = $c->contact;
                        $phone = $contact?->phone;
                        $lastMsg = $c->messages->sortByDesc('created_at')->first();
                        $unread = WaInboxUiHelper::hasUnreadHint($lastMsg, (string) $c->status);
                    @endphp
                    <a
                        href="{{ route('whatsapp.inbox.index', $inboxQuery(['conversation_id' => $c->id])) }}"
                        class="wa-conv-row {{ $activeId === $c->id ? 'is-active' : '' }}"
                    >
                        <div class="wa-avatar" aria-hidden="true">{{ WaInboxUiHelper::initials($phone, $contact?->customer_type) }}</div>
                        <div class="wa-conv-body">
                            <div class="wa-conv-top">
                                <span class="wa-conv-name">{{ WaInboxUiHelper::displayLabel($phone, $contact?->customer_type) }}</span>
                                <span class="wa-conv-time">{{ WaInboxUiHelper::listTime($c->last_message_at) }}</span>
                            </div>
                            <p class="wa-conv-preview">
                                @if($lastMsg)
                                    {{ $lastMsg->direction === 'out' ? __('whatsapp::whatsapp.you_prefix') : '' }}{{ WaInboxUiHelper::previewText($lastMsg) }}
                                @else
                                    {{ __('whatsapp::whatsapp.no_messages_preview') }}
                                @endif
                            </p>
                            <div class="wa-conv-meta">
                                <span class="wa-status-dot {{ WaInboxUiHelper::statusColorClass((string) $c->status) }}"></span>
                                <span class="wa-status-label">{{ __('whatsapp::whatsapp.status_' . $c->status) }}</span>
                                @if($c->assigned_agent_id && ($assigneeName = ($agentNames[$c->assigned_agent_id] ?? null)))
                                    <span class="wa-assignee-label" title="{{ __('whatsapp::whatsapp.assigned_to') }}">{{ $assigneeName }}</span>
                                @endif
                                @if($unread)
                                    <span class="wa-unread" title="{{ __('whatsapp::whatsapp.unread_hint') }}">1</span>
                                @endif
                            </div>
                            @if($c->tags?->isNotEmpty())
                                <div class="wa-conv-tags">
                                    @foreach($c->tags as $convTag)
                                        <span class="wa-tag-pill">{{ WhatsappConversationTagCatalog::label($convTag->tag) }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </a>
                @empty
                    <div class="p-4 text-center text-muted small">{{ __('whatsapp::whatsapp.inbox_empty') }}</div>
                @endforelse
            </div>
            @if($conversations->hasPages())
                <div class="wa-inbox-pagination">{{ $conversations->appends(request()->query())->links() }}</div>
            @endif
        </aside>

        <main class="wa-inbox-main {{ $conversation ? '' : 'wa-inbox-main--empty' }}">
            @if(!$conversation)
                <div class="text-center p-4">
                    <p class="mb-0 fw-semibold" style="color: var(--wa-graphite);">{{ __('whatsapp::whatsapp.select_conversation') }}</p>
                    <p class="small text-muted mt-1">{{ __('whatsapp::whatsapp.select_conversation_hint') }}</p>
                </div>
            @else
                @php
                    $contact = $conversation->contact;
                    $phone = $contact?->phone;
                    $windowOpen = $conversation->conversation_window_expires_at && now()->lte($conversation->conversation_window_expires_at);
                    $threadMessages = $conversation->messages()->orderBy('created_at')->take(50)->get();
                    $ctxMaintenanceId = ($inboxContext ?? [])['maintenance']['id'] ?? null;
                    $lastDateKey = null;
                @endphp

                <header class="wa-chat-header">
                    <a href="{{ route('whatsapp.inbox.index', ['search' => $search]) }}" class="wa-inbox-back d-lg-none">
                        {{ __('whatsapp::whatsapp.back_to_list') }}
                    </a>
                    <div class="wa-avatar" aria-hidden="true">{{ WaInboxUiHelper::initials($phone, $contact?->customer_type) }}</div>
                    <div class="wa-chat-header-info">
                        <p class="wa-chat-header-name">{{ WaInboxUiHelper::displayLabel($phone, $contact?->customer_type) }}</p>
                        <p class="wa-chat-header-sub">
                            <span class="wa-status-dot {{ WaInboxUiHelper::statusColorClass((string) $conversation->status) }}"></span>
                            {{ __('whatsapp::whatsapp.status_' . $conversation->status) }}
                            @if($contact?->customer_type)
                                &middot; {{ ucfirst((string) $contact->customer_type) }}
                            @endif
                        </p>
                    </div>
                    <div class="wa-chat-header-actions d-flex flex-wrap gap-2 align-items-center">
                        <form method="POST" action="{{ route('whatsapp.inbox.assign', $conversation->id) }}" class="wa-assign-form m-0">
                            @csrf
                            <select name="assigned_agent_id" class="form-select form-select-sm" style="min-width: 130px;">
                                <option value="">{{ __('whatsapp::whatsapp.assign_none') }}</option>
                                @foreach($assignableAgents ?? [] as $agent)
                                    <option value="{{ $agent->id }}" @selected((int) $conversation->assigned_agent_id === (int) $agent->id)>{{ $agent->name }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('whatsapp::whatsapp.assign_save') }}</button>
                            <button type="submit" name="assign_to_me" value="1" class="btn btn-sm wa-btn-gold">{{ __('whatsapp::whatsapp.assign_me') }}</button>
                        </form>
                        <form method="POST" action="{{ route('whatsapp.inbox.status', $conversation->id) }}" class="d-flex gap-2 m-0 align-items-center">
                            @csrf
                            <select name="status" class="form-select form-select-sm" style="min-width: 110px;">
                                @foreach(['open', 'pending', 'resolved'] as $status)
                                    <option value="{{ $status }}" @selected($conversation->status === $status)>{{ __('whatsapp::whatsapp.status_' . $status) }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm wa-btn-send">{{ __('whatsapp::whatsapp.save') }}</button>
                        </form>
                    </div>
                </header>

                <div class="wa-thread" role="log" aria-live="polite">
                    @forelse($threadMessages as $m)
                        @php
                            $dateKey = $m->created_at?->toDateString();
                            $isFailed = $m->status === 'failed';
                        @endphp
                        @if($dateKey && $dateKey !== $lastDateKey)
                            <div class="wa-date-sep">{{ WaInboxUiHelper::dateSeparatorLabel($m->created_at) }}</div>
                            @php $lastDateKey = $dateKey; @endphp
                        @endif
                        <div class="wa-bubble-row wa-bubble-row--{{ $m->direction }}">
                            <div class="wa-bubble {{ $isFailed ? 'wa-bubble--failed' : '' }}">
                                @if($m->type === 'template')
                                    <span class="wa-bubble-type">{{ __('whatsapp::whatsapp.msg_type_template') }}</span>
                                @elseif($m->type === 'text')
                                    <span class="wa-bubble-type">{{ __('whatsapp::whatsapp.msg_type_text') }}</span>
                                @elseif($m->type === 'image' || $m->type === 'sticker')
                                    <span class="wa-bubble-type">{{ __('whatsapp::whatsapp.msg_type_image') }}</span>
                                @elseif($m->type === 'document')
                                    <span class="wa-bubble-type">{{ __('whatsapp::whatsapp.msg_type_document') }}</span>
                                @elseif($m->type === 'video')
                                    <span class="wa-bubble-type">{{ __('whatsapp::whatsapp.msg_type_video') }}</span>
                                @elseif($m->type === 'audio')
                                    <span class="wa-bubble-type">{{ __('whatsapp::whatsapp.msg_type_audio') }}</span>
                                @endif
                                @if($m->media_url && in_array($m->type, ['image', 'sticker'], true))
                                    <a href="{{ route('whatsapp.inbox.media', $m) }}" target="_blank" rel="noopener">
                                        <img src="{{ route('whatsapp.inbox.media', $m) }}" alt="" class="wa-bubble-media" loading="lazy">
                                    </a>
                                @elseif($m->media_url && in_array($m->type, ['document', 'video', 'audio'], true))
                                    <a href="{{ route('whatsapp.inbox.media', $m) }}" target="_blank" rel="noopener" class="wa-bubble-doc">
                                        {{ $m->displayBody() !== '' ? $m->displayBody() : __('whatsapp::whatsapp.view_document') }}
                                    </a>
                                @endif
                                @if($m->direction === 'in' && $m->media_url && $ctxMaintenanceId)
                                    <form method="POST" action="{{ route('whatsapp.inbox.attach-maintenance', [$conversation, $m]) }}" class="mt-1 mb-1">
                                        @csrf
                                        <input type="hidden" name="maintenance_id" value="{{ $ctxMaintenanceId }}">
                                        <button type="submit" class="btn btn-link btn-sm p-0 wa-bubble-doc">{{ __('whatsapp::whatsapp.attach_to_mr') }}</button>
                                    </form>
                                @endif
                                @if($m->displayBody() !== '' && ! ($m->media_url && in_array($m->type, ['document', 'video', 'audio'], true)))
                                    <p class="wa-bubble-text">{{ $m->displayBody() }}</p>
                                @endif
                                <div class="wa-bubble-foot">
                                    <span class="wa-bubble-time">{{ WaInboxUiHelper::bubbleTime($m->created_at) }}</span>
                                    @if($m->direction === 'out')
                                        @if($isFailed)
                                            <span class="wa-delivery wa-delivery--failed">{{ __('whatsapp::whatsapp.delivery_failed') }}</span>
                                        @elseif($m->status === 'read')
                                            <span class="wa-delivery wa-delivery--read" title="{{ __('whatsapp::whatsapp.delivery_read') }}">
                                                <span class="wa-delivery__tick"></span><span class="wa-delivery__tick"></span>
                                            </span>
                                        @elseif($m->status === 'delivered')
                                            <span class="wa-delivery wa-delivery--delivered" title="{{ __('whatsapp::whatsapp.delivery_delivered') }}">
                                                <span class="wa-delivery__tick"></span><span class="wa-delivery__tick"></span>
                                            </span>
                                        @else
                                            <span class="wa-delivery wa-delivery--sent" title="{{ __('whatsapp::whatsapp.delivery_sent') }}">
                                                <span class="wa-delivery__tick"></span>
                                            </span>
                                        @endif
                                    @endif
                                </div>
                                @if($isFailed && ($err = $m->metaErrorSummary()))
                                    <div class="wa-bubble-error">
                                        <span class="wa-bubble-error-title">{{ __('whatsapp::whatsapp.meta_error') }}</span>
                                        {{ $err }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="wa-date-sep">{{ __('whatsapp::whatsapp.thread_empty') }}</div>
                    @endforelse
                </div>

                <div class="wa-tags-inline px-3 py-2" style="background:#f9fafb;border-top:1px solid var(--wa-border);">
                    <h6 class="mb-2 small fw-semibold" style="color:var(--wa-graphite);">{{ __('whatsapp::whatsapp.tags_title') }}</h6>
                    <p class="wa-context-placeholder mb-2">{{ __('whatsapp::whatsapp.tags_hint') }}</p>
                    <form method="POST" action="{{ route('whatsapp.inbox.tags', $conversation->id) }}" class="mb-0">
                        @csrf
                        <div class="wa-tags-editor">
                            @foreach(WhatsappConversationTagCatalog::keys() as $tagKey)
                                <label>
                                    <input type="checkbox" name="tags[]" value="{{ $tagKey }}" @checked(in_array($tagKey, $activeTags, true))>
                                    {{ WhatsappConversationTagCatalog::label($tagKey) }}
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn-sm text-white" style="background:#1F2937;border-color:#1F2937;">{{ __('whatsapp::whatsapp.tags_save') }}</button>
                    </form>
                </div>

                <div class="wa-notes-inline px-3 py-2" style="background:#fffbeb;border-top:1px solid #fde68a;">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <h6 class="mb-0 small fw-semibold" style="color:#92400e;">{{ __('whatsapp::whatsapp.notes_title') }}</h6>
                        <span class="wa-notes-badge">{{ __('whatsapp::whatsapp.notes_team_only') }}</span>
                    </div>
                    @forelse($conversationNotes ?? [] as $note)
                        <div class="wa-note-item">
                            <div class="wa-note-meta">
                                {{ $note->authorLabel() }}
                                &middot; {{ $note->created_at?->format('d M Y H:i') }}
                            </div>
                            <p class="wa-note-body">{{ $note->body }}</p>
                        </div>
                    @empty
                        <p class="wa-context-placeholder mb-2">{{ __('whatsapp::whatsapp.notes_empty') }}</p>
                    @endforelse
                    <form method="POST" action="{{ route('whatsapp.inbox.notes', $conversation->id) }}" class="wa-note-form mb-0">
                        @csrf
                        <textarea name="body" class="form-control form-control-sm" rows="2" maxlength="5000" placeholder="{{ __('whatsapp::whatsapp.notes_placeholder') }}" required></textarea>
                        <button type="submit" class="btn btn-sm mt-2 text-white" style="background:#B89A4A;border-color:#B89A4A;">{{ __('whatsapp::whatsapp.notes_add') }}</button>
                    </form>
                </div>

                <div class="wa-composer">
                    @php
                        $ctx = $inboxContext ?? [];
                        $quickLinks = array_filter([
                            !empty($ctx['customer']['url']) ? ['url' => $ctx['customer']['url'], 'label' => __('whatsapp::whatsapp.quick_customer')] : null,
                            !empty($ctx['property']['url']) ? ['url' => $ctx['property']['url'], 'label' => __('whatsapp::whatsapp.quick_property')] : null,
                            !empty($ctx['agreement']['url']) ? ['url' => $ctx['agreement']['url'], 'label' => __('whatsapp::whatsapp.quick_agreement')] : null,
                            !empty($ctx['maintenance']['url']) ? ['url' => $ctx['maintenance']['url'], 'label' => __('whatsapp::whatsapp.quick_maintenance')] : null,
                        ]);
                    @endphp
                    @if($windowOpen && ($quickLinks || ($cannedReplies ?? collect())->isNotEmpty()))
                        <div class="wa-quick-bar">
                            @if($quickLinks)
                                <span class="wa-quick-bar-label">{{ __('whatsapp::whatsapp.quick_actions') }}</span>
                                @foreach($quickLinks as $link)
                                    <a href="{{ $link['url'] }}" class="wa-quick-link" target="_blank" rel="noopener">{{ $link['label'] }}</a>
                                @endforeach
                            @endif
                            @if(($cannedReplies ?? collect())->isNotEmpty())
                                <span class="wa-quick-bar-label {{ $quickLinks ? 'ms-2' : '' }}">{{ __('whatsapp::whatsapp.canned_insert') }}</span>
                                @foreach($cannedReplies as $snippet)
                                    <button type="button" class="wa-quick-chip" data-wa-snippet="{{ e($snippet->bodyForLocale()) }}" title="{{ $snippet->title }}">{{ $snippet->title }}</button>
                                @endforeach
                            @endif
                        </div>
                    @endif
                    <form method="POST" action="{{ route('whatsapp.inbox.reply', $conversation->id) }}" class="mb-0" id="wa-reply-form" enctype="multipart/form-data">
                        @csrf
                        @if($windowOpen)
                            <div class="wa-composer-reply">
                                <textarea id="wa-reply-message" name="message" rows="1" placeholder="{{ __('whatsapp::whatsapp.reply_placeholder') }}"></textarea>
                                <label class="btn btn-sm btn-outline-secondary mb-0" title="{{ __('whatsapp::whatsapp.send_image') }}">
                                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="d-none" onchange="if(this.files.length){document.getElementById('wa-reply-form').submit();}">
                                    {{ __('whatsapp::whatsapp.send_image') }}
                                </label>
                                <button type="submit" class="btn wa-btn-send">{{ __('whatsapp::whatsapp.send') }}</button>
                            </div>
                        @else
                            <div class="alert alert-warning py-2 px-3 mb-2 small mb-2">{{ __('whatsapp::whatsapp.manual_reply_templates_only') }}</div>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <select name="template_id" class="form-select form-select-sm flex-grow-1">
                                    <option value="">{{ __('whatsapp::whatsapp.select_template') }}</option>
                                    @foreach($templates as $t)
                                        <option value="{{ $t->id }}">{{ $t->meta_template_name }} ({{ $t->language }})</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-sm wa-btn-send">{{ __('whatsapp::whatsapp.send') }}</button>
                            </div>
                        @endif
                    </form>
                </div>
            @endif
        </main>

        <aside class="wa-inbox-context d-none d-xl-flex">
            <div class="wa-context-head">{{ __('whatsapp::whatsapp.context_title') }}</div>
            @if(!$conversation)
                <div class="wa-context-section">
                    <div class="wa-skeleton-line" style="width:90%"></div>
                    <div class="wa-skeleton-line" style="width:70%"></div>
                    <p class="wa-context-placeholder mt-2">{{ __('whatsapp::whatsapp.context_select_conversation') }}</p>
                </div>
            @else
                @php
                    $ctx = $inboxContext ?? [];
                    $ctxCustomer = $ctx['customer'] ?? null;
                    $ctxProperty = $ctx['property'] ?? null;
                    $ctxTenancy = $ctx['tenancy'] ?? null;
                    $ctxAgreement = $ctx['agreement'] ?? null;
                    $ctxMaintenance = $ctx['maintenance'] ?? null;
                @endphp
                <div class="wa-context-section">
                    <h6>{{ __('whatsapp::whatsapp.assign_agent') }}</h6>
                    @php $currentAssignee = WhatsappInboxAgentHelper::nameForId($conversation->assigned_agent_id, $agentNames ?? []); @endphp
                    @if($currentAssignee)
                        <p class="wa-context-value mb-0">{{ $currentAssignee }}</p>
                        <p class="wa-context-meta">{{ __('whatsapp::whatsapp.assigned_to') }}</p>
                    @else
                        <p class="wa-context-placeholder mb-0">{{ __('whatsapp::whatsapp.assign_none') }}</p>
                    @endif
                </div>
                <div class="wa-context-section">
                    <h6>{{ __('whatsapp::whatsapp.context_customer') }}</h6>
                    @if($ctxCustomer)
                        <p class="wa-context-value">{{ $ctxCustomer['name'] }}</p>
                        <p class="wa-context-meta">
                            {{ $ctxCustomer['phone'] }}
                            @if(!empty($ctxCustomer['role']))
                                &middot; {{ $ctxCustomer['role'] }}
                            @endif
                            &middot; #{{ $ctxCustomer['id'] }}
                        </p>
                        @if(!empty($ctxCustomer['url']))
                            <a href="{{ $ctxCustomer['url'] }}" class="wa-context-link" target="_blank" rel="noopener">{{ __('whatsapp::whatsapp.context_view_customer') }}</a>
                        @endif
                    @else
                        <p class="wa-context-value">{{ WaInboxUiHelper::displayLabel($phone, $contact?->customer_type) }}</p>
                        @if($contact?->customer_type)
                            <p class="wa-context-meta mb-0">{{ ucfirst((string) $contact->customer_type) }} @if($contact?->customer_id)#{{ $contact->customer_id }}@endif</p>
                        @endif
                    @endif
                </div>
                <div class="wa-context-section">
                    <h6>{{ __('whatsapp::whatsapp.context_property') }}</h6>
                    @if($ctxProperty)
                        <p class="wa-context-value">{{ $ctxProperty['title'] }}</p>
                        <p class="wa-context-meta">#{{ $ctxProperty['id'] }}</p>
                        @if(!empty($ctxProperty['url']))
                            <a href="{{ $ctxProperty['url'] }}" class="wa-context-link" target="_blank" rel="noopener">{{ __('whatsapp::whatsapp.context_view_property') }}</a>
                        @endif
                    @else
                        <p class="wa-context-placeholder">{{ __('whatsapp::whatsapp.context_no_property') }}</p>
                    @endif
                </div>
                <div class="wa-context-section">
                    <h6>{{ __('whatsapp::whatsapp.context_agreement') }}</h6>
                    @if($ctxAgreement)
                        <p class="wa-context-value">#{{ $ctxAgreement['id'] }}</p>
                        <p class="wa-context-meta">{{ __('whatsapp::whatsapp.context_status') }}: {{ $ctxAgreement['status'] }}</p>
                        @if(!empty($ctxAgreement['url']))
                            <a href="{{ $ctxAgreement['url'] }}" class="wa-context-link" target="_blank" rel="noopener">{{ __('whatsapp::whatsapp.context_view_agreement') }}</a>
                        @endif
                    @else
                        <p class="wa-context-placeholder">{{ __('whatsapp::whatsapp.context_no_agreement') }}</p>
                    @endif
                </div>
                @if($ctxTenancy)
                    <div class="wa-context-section">
                        <h6>{{ __('whatsapp::whatsapp.context_tenancy') }}</h6>
                        <p class="wa-context-value">#{{ $ctxTenancy['id'] }}</p>
                        <p class="wa-context-meta">{{ __('whatsapp::whatsapp.context_status') }}: {{ $ctxTenancy['status'] }}</p>
                        @if(!empty($ctxTenancy['url']))
                            <a href="{{ $ctxTenancy['url'] }}" class="wa-context-link" target="_blank" rel="noopener">{{ __('whatsapp::whatsapp.context_view_tenancy') }}</a>
                        @endif
                    </div>
                @endif
                <div class="wa-context-section">
                    <h6>{{ __('whatsapp::whatsapp.context_maintenance') }}</h6>
                    @if($ctxMaintenance)
                        <p class="wa-context-value">{{ $ctxMaintenance['label'] }}</p>
                        <p class="wa-context-meta">{{ __('whatsapp::whatsapp.context_status') }}: {{ $ctxMaintenance['status'] }}</p>
                        @if(!empty($ctxMaintenance['url']))
                            <a href="{{ $ctxMaintenance['url'] }}" class="wa-context-link" target="_blank" rel="noopener">{{ __('whatsapp::whatsapp.context_view_maintenance') }}</a>
                        @endif
                    @else
                        <p class="wa-context-placeholder">{{ __('whatsapp::whatsapp.context_no_maintenance') }}</p>
                    @endif
                </div>
                @php
                    $quickLinks = array_filter([
                        !empty($ctxCustomer['url']) ? ['url' => $ctxCustomer['url'], 'label' => __('whatsapp::whatsapp.context_view_customer')] : null,
                        !empty($ctxProperty['url']) ? ['url' => $ctxProperty['url'], 'label' => __('whatsapp::whatsapp.context_view_property')] : null,
                        !empty($ctxAgreement['url']) ? ['url' => $ctxAgreement['url'], 'label' => __('whatsapp::whatsapp.context_view_agreement')] : null,
                        !empty($ctxMaintenance['url']) ? ['url' => $ctxMaintenance['url'], 'label' => __('whatsapp::whatsapp.context_view_maintenance')] : null,
                    ]);
                @endphp
                @if(count($quickLinks) > 1)
                    <div class="wa-context-section">
                        <h6>{{ __('whatsapp::whatsapp.context_quick_links') }}</h6>
                        <div class="wa-context-links">
                            @foreach($quickLinks as $link)
                                <a href="{{ $link['url'] }}" class="wa-context-link" target="_blank" rel="noopener">{{ $link['label'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </aside>
    </div>
</section>
@if($conversation ?? null)
<script>
(function () {
    document.querySelectorAll('[data-wa-snippet]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var ta = document.getElementById('wa-reply-message');
            if (!ta) return;
            var text = btn.getAttribute('data-wa-snippet') || '';
            if (!text) return;
            ta.value = ta.value ? (ta.value.trimEnd() + '\n' + text) : text;
            ta.focus();
        });
    });
})();
</script>
@endif
@endsection
