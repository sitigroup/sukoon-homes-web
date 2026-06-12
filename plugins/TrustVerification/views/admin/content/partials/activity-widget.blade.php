<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="tv-cms-widget">
            <div class="tv-cms-widget__title">Most edited blocks</div>
            @forelse($mostEdited as $key => $count)
                <div class="tv-cms-widget__item d-flex justify-content-between gap-2">
                    <code class="small">{{ $key }}</code>
                    <span class="text-muted">{{ $count }} edits</span>
                </div>
            @empty
                <p class="small text-muted mb-0">No edit activity yet.</p>
            @endforelse
        </div>
    </div>
    <div class="col-md-6">
        <div class="tv-cms-widget">
            <div class="tv-cms-widget__title">Recent edits</div>
            @forelse($recentEdits as $log)
                <div class="tv-cms-widget__item">
                    <div class="d-flex justify-content-between gap-2">
                        <span class="fw-medium">{{ $log->metadata['content_key'] ?? $log->description }}</span>
                        <span class="text-muted text-nowrap">{{ $log->created_at?->diffForHumans() }}</span>
                    </div>
                    <div class="text-muted small">
                        {{ str_replace('_', ' ', $log->action) }}
                        · {{ $log->actorLabel() }}
                        @if(!empty($log->metadata['version']))
                            · v{{ $log->metadata['version'] }}
                        @endif
                    </div>
                </div>
            @empty
                <p class="small text-muted mb-0">No recent content changes.</p>
            @endforelse
        </div>
    </div>
</div>
