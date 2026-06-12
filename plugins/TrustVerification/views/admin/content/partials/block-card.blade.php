@php
    $block = $item['block'];
    $headline = $item['headline'];
    $snippet = $item['snippet'];
    $previewId = 'preview-'.$block->id;
@endphp
<div class="tv-cms-block {{ !empty($sortable) ? 'is-sortable' : '' }}" data-block-id="{{ $block->id }}">
    <div class="d-flex flex-wrap align-items-start gap-2">
        @if(!empty($sortable))
            <span class="tv-cms-block__drag" title="Drag to reorder" aria-hidden="true">⋮⋮</span>
        @endif
        <div class="flex-grow-1 min-width-0">
            <h3 class="tv-cms-block__title">{{ $headline }}</h3>
            <div class="tv-cms-block__key">Key: {{ $block->content_key }}</div>
            <div class="tv-cms-block__meta">
                <span>Version: <strong>v{{ $block->version }}</strong></span>
                <span>Updated: {{ $block->updated_at?->format('d M') ?? '—' }}</span>
                <span class="{{ \App\Plugins\TrustVerification\Support\TrustVerificationContentPreview::statusClass($block) }}">
                    {{ \App\Plugins\TrustVerification\Support\TrustVerificationContentPreview::statusLabel($block) }}
                </span>
                @if($block->updated_by)
                    <span>By admin #{{ $block->updated_by }}</span>
                @endif
            </div>
            <div id="{{ $previewId }}" class="tv-cms-block__preview d-none">
                <div class="tv-cms-block__preview-label">Current</div>
                <span class="tv-cms-inline-snippet">"{{ $snippet }}"</span>
            </div>
        </div>
        <div class="tv-cms-block__actions d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm tv-cms-btn tv-cms-btn--secondary tv-cms-toggle-preview"
                    data-target="{{ $previewId }}" aria-expanded="false">
                Preview
            </button>
            <a href="{{ route('trust-verification.content.edit', $block) }}"
               class="btn btn-sm tv-cms-btn tv-cms-btn--primary">Edit</a>
        </div>
    </div>
</div>
