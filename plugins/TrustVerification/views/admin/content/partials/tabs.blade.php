<div class="tv-cms-tabs-wrap">
    <nav class="tv-cms-tabs" aria-label="Content groups">
        @foreach($tabs as $t)
            @php($meta = $tabMeta[$t] ?? ['label' => ucfirst($t), 'icon' => 'bi-folder'])
            <a href="{{ route('trust-verification.content.index', array_merge(request()->only(['q', 'status', 'recent']), ['tab' => $t])) }}"
               class="tv-cms-tab {{ $tab === $t ? 'is-active' : '' }}">
                <i class="bi {{ $meta['icon'] }}" aria-hidden="true"></i>
                {{ $meta['label'] }}
            </a>
        @endforeach
    </nav>
</div>
