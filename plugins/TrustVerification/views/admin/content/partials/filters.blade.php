<form method="get" class="tv-cms-toolbar">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-5">
            <label class="form-label small text-muted mb-1">Search</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="search" name="q" class="form-control border-start-0"
                       placeholder="Search content…" value="{{ $filters['q'] ?? '' }}">
            </div>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>All</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                <option value="draft" @selected(($filters['status'] ?? '') === 'draft')>Draft</option>
            </select>
        </div>
        <div class="col-6 col-md-3 d-flex align-items-end">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="recent" value="1" id="tv-cms-recent"
                       @checked($filters['recent'] ?? false)>
                <label class="form-check-label small" for="tv-cms-recent">Updated recently (7d)</label>
            </div>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm tv-cms-btn tv-cms-btn--primary flex-grow-1">Apply</button>
            <a href="{{ route('trust-verification.content.index', ['tab' => $tab]) }}"
               class="btn btn-sm tv-cms-btn tv-cms-btn--secondary">Reset</a>
        </div>
    </div>
</form>
