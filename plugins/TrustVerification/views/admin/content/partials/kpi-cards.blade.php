<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="tv-cms-kpi">
            <div class="tv-cms-kpi__label">Content blocks</div>
            <div class="tv-cms-kpi__value">{{ $stats['total'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="tv-cms-kpi">
            <div class="tv-cms-kpi__label">Published</div>
            <div class="tv-cms-kpi__value">{{ $stats['published'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="tv-cms-kpi">
            <div class="tv-cms-kpi__label">Draft</div>
            <div class="tv-cms-kpi__value">{{ $stats['draft'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="tv-cms-kpi">
            <div class="tv-cms-kpi__label">Last updated</div>
            <div class="tv-cms-kpi__value tv-cms-kpi__value--sub">
                {{ ($stats['last_updated'] ?? null)?->format('d M Y') ?? '—' }}
            </div>
        </div>
    </div>
</div>
