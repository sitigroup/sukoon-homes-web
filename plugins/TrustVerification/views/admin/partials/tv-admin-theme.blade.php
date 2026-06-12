<style>
    /* Trust Verification — premium admin (scoped under .tv-admin-page) */
    .tv-admin-page {
        --tv-graphite: #111827;
        --tv-graphite-hover: #000000;
        --tv-text-primary: #111827;
        --tv-text-secondary: #374151;
        --tv-accent: #111827;
        --tv-accent-soft: rgba(17, 24, 39, 0.08);
        --tv-bg: #FFFFFF;
        --tv-page-bg: #F9FAFB;
        --tv-section: #F9FAFB;
        --tv-border: #E5E7EB;
        --tv-border-strong: #D1D5DB;
        --tv-input-border: #D1D5DB;
        --tv-input-focus: #111827;
        --tv-muted: #6B7280;
        --tv-placeholder: #9CA3AF;
        --tv-table-helper: #4B5563;
        --tv-shadow: 0 4px 24px rgba(17, 24, 39, 0.08);
        --tv-shadow-sm: 0 2px 10px rgba(17, 24, 39, 0.06);
        --tv-shadow-btn: 0 2px 8px rgba(17, 24, 39, 0.12);
        --tv-radius: 16px;
        --tv-field-gap: 16px;
        --tv-section-gap: 24px;

        color: var(--tv-text-primary) !important;
        font-size: 0.9375rem !important;
        line-height: 1.55 !important;
        background: var(--tv-page-bg) !important;
        border-radius: var(--tv-radius) !important;
        padding: 1.25rem 1.35rem 1.75rem !important;
        margin: 0 0 1rem !important;
    }
    .tv-admin-page .text-muted { color: var(--tv-muted) !important; }
    .tv-admin-page .small { font-size: 0.875rem !important; }

    /* Override WRTeam section/card defaults */
    .tv-admin-page .card:not(.tv-admin-card):not(.tv-card) {
        border: 1px solid var(--tv-border-strong) !important;
        border-radius: var(--tv-radius) !important;
        box-shadow: var(--tv-shadow-sm) !important;
    }

    /* Typography */
    .tv-admin-page .tv-admin-header,
    .tv-admin-page .tv-page-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.85rem;
        margin-bottom: 1.35rem;
    }
    .tv-admin-page .tv-admin-header__title,
    .tv-admin-page .tv-page-header__title,
    .tv-admin-page h1.tv-admin-header__title {
        margin: 0;
        font-size: 1.5rem !important;
        font-weight: 700 !important;
        color: var(--tv-text-primary) !important;
        letter-spacing: -0.02em;
    }
    .tv-admin-page .tv-admin-header__meta,
    .tv-admin-page .tv-page-header__meta {
        font-size: 0.9375rem !important;
        color: var(--tv-muted) !important;
    }
    .tv-admin-page .tv-admin-section-title {
        font-size: 16px !important;
        font-weight: 700 !important;
        color: var(--tv-text-primary) !important;
        margin: 0 0 0.85rem;
    }
    .tv-admin-page .tv-section-label {
        font-size: 0.8125rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--tv-muted) !important;
        margin: 1rem 0 0.5rem;
    }

    /* Forms — no cramped tiny controls */
    .tv-admin-page .form-label,
    .tv-admin-page label.form-label {
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        color: var(--tv-text-primary) !important;
        margin-bottom: 0.35rem !important;
    }
    .tv-admin-page .form-control,
    .tv-admin-page .form-select {
        font-size: 0.9375rem !important;
        color: var(--tv-text-primary) !important;
        background-color: var(--tv-bg) !important;
        border: 1px solid var(--tv-input-border) !important;
        border-radius: 10px !important;
        padding: 0.5rem 0.75rem !important;
        min-height: 2.5rem;
    }
    .tv-admin-page .form-control::placeholder,
    .tv-admin-page .form-select::placeholder,
    .tv-admin-page textarea.form-control::placeholder {
        color: var(--tv-placeholder) !important;
        opacity: 1 !important;
    }
    .tv-admin-page .form-control:focus,
    .tv-admin-page .form-select:focus,
    .tv-admin-page textarea.form-control:focus {
        border-color: var(--tv-input-focus) !important;
        box-shadow: 0 0 0 0.15rem rgba(17, 24, 39, 0.12) !important;
        color: var(--tv-text-primary) !important;
    }
    .tv-admin-page .form-control-sm,
    .tv-admin-page .form-select-sm {
        font-size: 0.875rem !important;
        padding: 0.45rem 0.65rem !important;
        min-height: 2.25rem;
    }
    .tv-admin-page .form-check-label { font-size: 0.9375rem !important; }

    /* Nav tabs */
    .tv-admin-page .tv-tab-nav {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.5rem;
        margin-bottom: 1.35rem;
        padding-bottom: 0.25rem;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .tv-admin-page .tv-tab-nav::-webkit-scrollbar { display: none; }
    .tv-admin-page .tv-tab {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.6rem 1.05rem;
        border-radius: 12px;
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        text-decoration: none !important;
        white-space: nowrap;
        border: 1px solid var(--tv-graphite) !important;
        background: var(--tv-bg) !important;
        color: var(--tv-text-primary) !important;
        transition: background 0.15s, color 0.15s, box-shadow 0.15s;
    }
    .tv-admin-page .tv-tab:hover {
        background: var(--tv-section) !important;
        color: var(--tv-graphite-hover) !important;
    }
    .tv-admin-page .tv-tab.is-active {
        background: var(--tv-text-primary) !important;
        border-color: var(--tv-text-primary) !important;
        color: #FFFFFF !important;
        box-shadow: var(--tv-shadow-btn);
    }
    .tv-admin-page .tv-tab.is-active .bi { color: rgba(255, 255, 255, 0.92) !important; }
    .tv-admin-page .tv-tab .bi { font-size: 1rem; color: var(--tv-muted); }

    /* Grids */
    .tv-admin-page .tv-admin-grid { display: grid; gap: 1rem; }
    .tv-admin-page .tv-admin-grid--kpi { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .tv-admin-page .tv-admin-grid--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .tv-admin-page .tv-admin-grid--packages { grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); }
    @media (max-width: 1199.98px) {
        .tv-admin-page .tv-admin-grid--kpi { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 767.98px) {
        .tv-admin-page .tv-admin-grid--kpi { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .tv-admin-page .tv-admin-grid--2 { grid-template-columns: 1fr; }
        .tv-admin-page .tv-kpi__value { font-size: 1.45rem !important; }
        .tv-admin-page { padding: 1rem !important; }
    }

    /* Buttons */
    .tv-admin-page .tv-admin-btn-primary,
    .tv-admin-page .tv-btn-primary,
    .tv-admin-page .btn.tv-admin-btn-primary,
    .tv-admin-page .btn.tv-btn-primary {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.4rem !important;
        border-radius: 12px !important;
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        padding: 0.55rem 1.05rem !important;
        background: var(--tv-text-primary) !important;
        border: 1px solid var(--tv-text-primary) !important;
        color: #FFFFFF !important;
        box-shadow: var(--tv-shadow-btn) !important;
    }
    .tv-admin-page .tv-admin-btn-primary:hover,
    .tv-admin-page .btn.tv-admin-btn-primary:hover {
        background: var(--tv-graphite-hover) !important;
        border-color: var(--tv-graphite-hover) !important;
        color: #FFFFFF !important;
    }
    .tv-admin-page .tv-admin-btn-secondary,
    .tv-admin-page .tv-btn-secondary,
    .tv-admin-page .btn.tv-admin-btn-secondary,
    .tv-admin-page .btn.tv-btn-secondary {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.4rem !important;
        border-radius: 12px !important;
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        padding: 0.55rem 1.05rem !important;
        background: var(--tv-bg) !important;
        border: 1px solid var(--tv-input-border) !important;
        color: var(--tv-text-primary) !important;
    }
    .tv-admin-page .tv-admin-btn-secondary:hover,
    .tv-admin-page .btn.tv-admin-btn-secondary:hover {
        background: var(--tv-section) !important;
        color: var(--tv-graphite-hover) !important;
    }

    /* Cards */
    .tv-admin-page .tv-admin-card,
    .tv-admin-page .tv-card {
        background: var(--tv-bg) !important;
        border: 1px solid var(--tv-border-strong) !important;
        border-radius: var(--tv-radius) !important;
        box-shadow: var(--tv-shadow-sm) !important;
        overflow: hidden;
        margin-bottom: 1rem;
    }
    .tv-admin-page .tv-card__header,
    .tv-admin-page .tv-admin-card__header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        padding: 1.05rem 1.3rem !important;
        border-bottom: 1px solid var(--tv-border) !important;
        background: var(--tv-bg) !important;
    }
    .tv-admin-page .tv-card__header strong,
    .tv-admin-page .tv-admin-card__header strong {
        font-size: 1.0625rem !important;
        font-weight: 700 !important;
        color: var(--tv-text-primary) !important;
    }
    .tv-admin-page .tv-card__body,
    .tv-admin-page .tv-admin-card__body { padding: 1.25rem 1.5rem !important; }
    .tv-admin-page .tv-card__body--flush,
    .tv-admin-page .tv-admin-card__body--flush { padding: 0 !important; }

    /* KPI */
    .tv-admin-page .tv-kpi {
        display: block;
        background: var(--tv-bg);
        border: 1px solid var(--tv-border-strong);
        border-radius: var(--tv-radius);
        box-shadow: var(--tv-shadow-sm);
        padding: 1.15rem 1.25rem;
        height: 100%;
        text-decoration: none !important;
        color: inherit !important;
        transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
    }
    .tv-admin-page a.tv-kpi:hover {
        border-color: var(--tv-graphite);
        box-shadow: var(--tv-shadow);
        transform: translateY(-2px);
        color: inherit !important;
    }
    .tv-admin-page .tv-kpi__label {
        font-size: 0.8125rem !important;
        font-weight: 600 !important;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--tv-muted) !important;
        margin-bottom: 0.45rem;
    }
    .tv-admin-page .tv-kpi__value {
        font-size: 1.85rem !important;
        font-weight: 700 !important;
        line-height: 1.1;
        color: var(--tv-text-primary) !important;
    }
    .tv-admin-page .tv-kpi--warning { border-color: rgba(217, 119, 6, 0.4); }
    .tv-admin-page .tv-kpi--warning .tv-kpi__value { color: #D97706 !important; }
    .tv-admin-page .tv-kpi--danger { border-color: rgba(220, 38, 38, 0.4); }
    .tv-admin-page .tv-kpi--danger .tv-kpi__value { color: #DC2626 !important; }
    .tv-admin-page .tv-kpi--success .tv-kpi__value { color: #059669 !important; }
    .tv-admin-page .tv-kpi--info .tv-kpi__value { color: #2563EB !important; }

    /* Filters */
    .tv-admin-page .tv-admin-filter,
    .tv-admin-page .tv-filter {
        background: var(--tv-bg) !important;
        border: 1px solid var(--tv-border-strong) !important;
        border-radius: var(--tv-radius) !important;
        padding: 1.2rem 1.3rem !important;
        margin-bottom: 1.2rem !important;
    }
    .tv-admin-page .tv-filter--chips {
        background: var(--tv-bg) !important;
        border: 1px solid var(--tv-border-strong) !important;
        border-radius: var(--tv-radius) !important;
        padding: 0.9rem 1.1rem !important;
    }
    .tv-admin-page .tv-quick-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        align-items: center;
        margin-bottom: 1.1rem;
    }
    .tv-admin-page .tv-quick-filters__label {
        font-size: 0.8125rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        color: var(--tv-muted) !important;
        margin-right: 0.25rem;
    }
    .tv-admin-page .tv-chip-filter {
        display: inline-flex;
        padding: 0.4rem 0.8rem;
        border-radius: 999px;
        font-size: 0.8125rem !important;
        font-weight: 600 !important;
        text-decoration: none !important;
        border: 1px solid var(--tv-border-strong) !important;
        background: var(--tv-bg) !important;
        color: var(--tv-text-primary) !important;
    }
    .tv-admin-page .tv-chip-filter:hover {
        background: var(--tv-section) !important;
    }
    .tv-admin-page .tv-chip-filter.is-active {
        background: var(--tv-text-primary) !important;
        border-color: var(--tv-text-primary) !important;
        color: #FFFFFF !important;
    }

    /* Chips */
    .tv-admin-page .tv-admin-chip-success,
    .tv-admin-page .tv-chip-active {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        background: rgba(5, 150, 105, 0.14) !important;
        color: #059669 !important;
    }
    .tv-admin-page .tv-admin-chip-muted,
    .tv-admin-page .tv-chip-neutral,
    .tv-admin-page .tv-chip-draft {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        background: rgba(107, 114, 128, 0.14) !important;
        color: #6B7280 !important;
    }
    .tv-admin-page .tv-admin-chip-warning,
    .tv-admin-page .tv-chip-warning {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        background: rgba(217, 119, 6, 0.14) !important;
        color: #D97706 !important;
    }
    .tv-admin-page .tv-admin-chip-danger,
    .tv-admin-page .tv-chip-danger {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        background: rgba(220, 38, 38, 0.14) !important;
        color: #DC2626 !important;
    }
    .tv-admin-page .tv-chip-info {
        display: inline-flex;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        background: rgba(37, 99, 235, 0.12) !important;
        color: #2563EB !important;
    }
    .tv-admin-page .tv-chip-gold {
        background: var(--tv-accent-soft) !important;
        color: var(--tv-text-secondary) !important;
        border: 1px solid var(--tv-input-border) !important;
    }

    /* Tables */
    .tv-admin-page .tv-admin-table,
    .tv-admin-page .table {
        --bs-table-striped-bg: rgba(248, 249, 250, 0.85) !important;
        font-size: 0.9375rem !important;
        margin-bottom: 0 !important;
        color: var(--tv-text-primary) !important;
    }
    .tv-admin-page .tv-admin-table thead th,
    .tv-admin-page .table thead th {
        font-size: 0.8125rem !important;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--tv-muted) !important;
        font-weight: 700 !important;
        border-bottom: 1px solid var(--tv-border-strong) !important;
        white-space: nowrap;
        padding: 0.9rem 0.75rem !important;
        background: var(--tv-section) !important;
    }
    .tv-admin-page .table tbody td {
        padding: 0.85rem 0.75rem !important;
        vertical-align: middle !important;
        border-color: var(--tv-border) !important;
    }
    .tv-admin-page .table tbody tr:hover { background: rgba(248, 249, 250, 0.95) !important; }
    .tv-admin-page .table-warning { --bs-table-bg: rgba(217, 119, 6, 0.1) !important; }
    .tv-admin-page .table-responsive {
        border-radius: 12px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* Package cards */
    .tv-admin-page .tv-package-card {
        background: var(--tv-bg);
        border: 1px solid var(--tv-border-strong);
        border-radius: var(--tv-radius);
        box-shadow: var(--tv-shadow-sm);
        padding: 1.1rem 1.2rem;
        height: 100%;
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }
    .tv-admin-page .tv-package-card__title {
        font-size: 1rem !important;
        font-weight: 700 !important;
        margin: 0;
        color: var(--tv-text-primary) !important;
    }
    .tv-admin-page .tv-package-card__meta {
        font-size: 0.875rem !important;
        color: var(--tv-muted) !important;
    }
    .tv-admin-page .tv-package-card__footer {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        align-items: center;
        margin-top: auto;
        padding-top: 0.5rem;
    }

    /* List / entity cards */
    .tv-admin-page .tv-admin-list-card {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.85rem;
        padding: 1.1rem 1.25rem;
        border: 1px solid var(--tv-border-strong);
        border-radius: var(--tv-radius);
        background: var(--tv-bg);
        box-shadow: var(--tv-shadow-sm);
        margin-bottom: 0.85rem;
    }
    .tv-admin-page .tv-admin-list-card__title {
        font-size: 1.0625rem !important;
        font-weight: 700 !important;
        margin: 0 0 0.25rem;
        color: var(--tv-text-primary) !important;
    }
    .tv-admin-page .tv-admin-list-card__meta {
        font-size: 0.875rem !important;
        color: var(--tv-muted) !important;
    }
    .tv-admin-page .tv-admin-list-card__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        align-items: center;
    }
    .tv-admin-page .tv-admin-entity-card {
        border: 1px solid var(--tv-border-strong);
        border-radius: 14px;
        padding: 1.05rem 1.2rem;
        margin-bottom: 0.85rem;
        background: var(--tv-section);
    }
    .tv-admin-page .tv-admin-entity-card__title {
        font-size: 0.9375rem !important;
        font-weight: 700 !important;
    }

    /* Accordions */
    .tv-admin-page .tv-admin-accordion {
        border: 1px solid var(--tv-border-strong);
        border-radius: var(--tv-radius);
        background: var(--tv-bg);
        box-shadow: var(--tv-shadow-sm);
        margin-bottom: 1rem;
    }
    .tv-admin-page .tv-admin-accordion > summary {
        list-style: none;
        cursor: pointer;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.875rem 1rem;
        font-weight: 600 !important;
        font-size: 1rem !important;
        color: var(--tv-text-primary) !important;
        user-select: none;
    }
    .tv-admin-page .tv-admin-accordion > summary::-webkit-details-marker { display: none; }
    .tv-admin-page .tv-admin-accordion > summary::after {
        content: '';
        width: 0.55rem;
        height: 0.55rem;
        border-right: 2px solid var(--tv-muted);
        border-bottom: 2px solid var(--tv-muted);
        transform: rotate(45deg);
        margin-left: auto;
    }
    .tv-admin-page .tv-admin-accordion[open] > summary {
        border-bottom: 1px solid var(--tv-border);
    }
    .tv-admin-page .tv-admin-accordion[open] > summary::after {
        transform: rotate(-135deg);
        margin-top: 0.25rem;
    }
    .tv-admin-page .tv-admin-accordion__body { padding: 1.25rem 1.5rem; }
    .tv-admin-page .tv-admin-accordion__meta {
        font-size: 0.875rem !important;
        color: var(--tv-muted) !important;
        font-weight: 500;
    }

    /* Upload zone */
    .tv-admin-page .tv-upload-zone {
        border: 2px dashed var(--tv-border-strong);
        border-radius: var(--tv-radius);
        padding: 1.25rem;
        background: var(--tv-section);
    }
    .tv-admin-page .tv-upload-zone input[type="file"] {
        font-size: 0.9375rem !important;
    }

    /* Sidebar sticky on automation */
    .tv-admin-page .tv-admin-sidebar {
        position: sticky;
        top: 1rem;
    }

    /* Empty state */
    .tv-admin-page .tv-admin-empty-state {
        text-align: center;
        padding: 2.75rem 1.5rem;
        color: var(--tv-muted) !important;
        font-size: 0.9375rem !important;
    }
    .tv-admin-page .tv-admin-empty-state__icon {
        font-size: 2.25rem;
        color: var(--tv-border-strong);
        margin-bottom: 0.85rem;
    }

    /* Misc */
    .tv-admin-page .tv-price-badge {
        font-weight: 700 !important;
        color: var(--tv-text-primary) !important;
    }
    .tv-admin-page .tv-help-block {
        background: var(--tv-section) !important;
        border: 1px solid var(--tv-input-border) !important;
        border-left: 3px solid var(--tv-text-primary) !important;
        border-radius: 12px !important;
        padding: 0.9rem 1.1rem !important;
        font-size: 0.875rem !important;
        color: var(--tv-text-secondary) !important;
        margin-bottom: 1rem;
    }
    .tv-admin-page .tv-link-back {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.875rem !important;
        font-weight: 600;
        color: var(--tv-text-primary) !important;
        text-decoration: none !important;
        margin-bottom: 0.85rem;
    }
    .tv-admin-page .tv-link-back:hover {
        color: var(--tv-graphite-hover) !important;
        text-decoration: underline !important;
    }
    .tv-admin-page .tv-alert-privacy {
        border-left: 4px solid var(--tv-text-primary) !important;
        background: var(--tv-section) !important;
        border-radius: 12px !important;
        color: var(--tv-text-secondary) !important;
    }
    .tv-admin-page .tv-detail-grid {
        display: grid;
        grid-template-columns: minmax(0, 5fr) minmax(0, 7fr);
        gap: 1rem;
    }
    @media (max-width: 991.98px) {
        .tv-admin-page .tv-detail-grid { grid-template-columns: 1fr; }
    }
    .tv-admin-page .alert-success,
    .tv-admin-page .alert-danger,
    .tv-admin-page .alert-warning {
        border-radius: 12px !important;
        font-size: 0.9375rem !important;
    }

    /* ── Readability hardening (contrast + spacing) ── */
    .tv-admin-page,
    .tv-admin-page p,
    .tv-admin-page li,
    .tv-admin-page td,
    .tv-admin-page th {
        color: var(--tv-text-primary);
    }

    .tv-admin-page .text-muted,
    .tv-admin-page .tv-admin-helper {
        color: var(--tv-muted) !important;
        font-size: 0.875rem !important;
    }

    .tv-admin-page .tv-admin-table-helper,
    .tv-admin-page .table tbody td .small,
    .tv-admin-page .table tbody td.text-muted,
    .tv-admin-page .tv-admin-timestamp {
        color: var(--tv-table-helper) !important;
        font-size: 0.875rem !important;
    }

    .tv-admin-page .tv-admin-card__header strong,
    .tv-admin-page .tv-card__header strong,
    .tv-admin-page .tv-page-header__title {
        color: var(--tv-text-primary) !important;
        font-weight: 700 !important;
    }

    .tv-admin-page .tv-admin-form-row {
        --bs-gutter-x: var(--tv-field-gap);
        --bs-gutter-y: var(--tv-field-gap);
    }

    .tv-admin-page .tv-admin-section-stack > * + * {
        margin-top: var(--tv-section-gap) !important;
    }

    .tv-admin-page .tv-admin-police-panel .tv-admin-card__body,
    .tv-admin-page .tv-admin-reference-panel .tv-admin-card__body {
        padding: 1.25rem 1.5rem !important;
    }

    .tv-admin-page .tv-admin-police-panel .tv-admin-meta-block {
        color: var(--tv-text-secondary) !important;
        font-size: 0.875rem !important;
        margin-bottom: var(--tv-section-gap) !important;
    }

    .tv-admin-page .tv-admin-police-panel .tv-admin-meta-block strong {
        color: var(--tv-text-primary) !important;
    }

    .tv-admin-page .tv-admin-police-panel .d-flex.flex-wrap.gap-2.mb-3 {
        margin-bottom: var(--tv-section-gap) !important;
        gap: 0.65rem !important;
    }

    .tv-admin-page .tv-admin-reference-panel .tv-admin-entity-card {
        padding: 1.25rem 1.5rem !important;
        margin-bottom: var(--tv-field-gap) !important;
        background: var(--tv-bg) !important;
        border-color: var(--tv-input-border) !important;
    }

    .tv-admin-page .tv-admin-reference-panel .tv-admin-entity-card__title {
        color: var(--tv-text-primary) !important;
        font-weight: 600 !important;
    }

    .tv-admin-page .tv-admin-reference-panel .tv-admin-entity-card .text-muted {
        color: var(--tv-text-secondary) !important;
    }

    .tv-admin-page .tv-admin-reference-panel .tv-admin-timestamp {
        color: var(--tv-table-helper) !important;
        font-size: 0.875rem !important;
        margin-bottom: 0.75rem !important;
    }

    .tv-admin-page .tv-admin-table thead th {
        color: var(--tv-table-helper) !important;
    }

    .tv-admin-page .form-label.small {
        color: var(--tv-text-secondary) !important;
        font-weight: 600 !important;
    }

    .tv-admin-page :disabled,
    .tv-admin-page .disabled {
        color: var(--tv-muted) !important;
        opacity: 0.72;
    }

    .tv-admin-page a.small:not(.btn) {
        color: var(--tv-text-primary) !important;
        font-weight: 600;
    }
</style>
