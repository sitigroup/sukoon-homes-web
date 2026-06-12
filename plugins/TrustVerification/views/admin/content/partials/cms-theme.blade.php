@include('trust-verification::admin.partials.tv-admin-theme')
<style>
    /* Content CMS — studio-specific (extends shared admin theme, monochrome) */
    .tv-cms { color: var(--tv-text-primary); }

    .tv-cms-kpi,
    .tv-kpi {
        background: var(--tv-bg);
        border: 1px solid var(--tv-input-border);
        border-radius: var(--tv-radius);
        box-shadow: var(--tv-shadow-sm);
        padding: 1rem 1.15rem;
        height: 100%;
    }
    .tv-cms-kpi__label,
    .tv-kpi__label {
        font-size: 0.8125rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--tv-muted);
        margin-bottom: 0.35rem;
    }
    .tv-cms-kpi__value,
    .tv-kpi__value {
        font-size: 1.65rem;
        font-weight: 700;
        color: var(--tv-text-primary);
        line-height: 1.1;
    }
    .tv-cms-kpi__value--sub { color: var(--tv-text-secondary); font-size: 1rem; font-weight: 600; }

    .tv-cms-tabs-wrap {
        position: sticky;
        top: 0;
        z-index: 20;
        background: var(--tv-bg);
        border-bottom: 1px solid var(--tv-border);
        margin: 0 -0.25rem 1.25rem;
        padding: 0.5rem 0.25rem 0;
    }
    .tv-cms-tabs {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.35rem;
        overflow-x: auto;
        scrollbar-width: none;
        padding-bottom: 0.5rem;
    }
    .tv-cms-tabs::-webkit-scrollbar { display: none; }
    .tv-cms-tab {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        white-space: nowrap;
        padding: 0.5rem 0.85rem;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 500;
        color: var(--tv-text-secondary);
        text-decoration: none;
        border: 1px solid transparent;
    }
    .tv-cms-tab:hover { color: var(--tv-text-primary); background: var(--tv-section); }
    .tv-cms-tab.is-active {
        color: var(--tv-text-primary);
        background: var(--tv-section);
        border-color: var(--tv-input-border);
        font-weight: 600;
        box-shadow: inset 2px 0 0 var(--tv-text-primary);
    }
    .tv-cms-tab .bi { color: var(--tv-muted); }
    .tv-cms-tab.is-active .bi { color: var(--tv-text-primary); }

    .tv-cms-toolbar,
    .tv-filter { background: var(--tv-section); border-radius: var(--tv-radius); padding: 1rem; margin-bottom: 1.25rem; }

    .tv-cms-block {
        background: var(--tv-bg);
        border: 1px solid var(--tv-border);
        border-radius: var(--tv-radius);
        box-shadow: var(--tv-shadow-sm);
        padding: 1.25rem 1.5rem;
        margin-bottom: 0.75rem;
    }
    .tv-cms-block__preview { border-left: 2px solid var(--tv-text-primary); }
    .tv-cms-status--active { background: rgba(5, 150, 105, 0.1); color: #059669; }
    .tv-cms-status--draft { background: rgba(107, 114, 128, 0.12); color: var(--tv-text-secondary); }
    .tv-cms-status {
        display: inline-flex;
        padding: 0.15rem 0.55rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .tv-cms-widget { background: var(--tv-bg); border: 1px solid var(--tv-border); border-radius: var(--tv-radius); padding: 1rem 1.15rem; height: 100%; }
    .tv-cms-widget__title { font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; color: var(--tv-muted); margin-bottom: 0.75rem; }
    .tv-cms-widget__item { padding: 0.5rem 0; border-bottom: 1px solid var(--tv-border); font-size: 0.875rem; color: var(--tv-text-primary); }
    .tv-cms-edit-split { display: grid; gap: 1.25rem; }
    @media (min-width: 992px) { .tv-cms-edit-split { grid-template-columns: 1fr 1fr; } }
    .tv-cms-preview-panel { background: var(--tv-section); border-radius: var(--tv-radius); padding: 1.25rem; min-height: 200px; color: var(--tv-text-primary); }
    .tv-cms-flow__step.is-current { box-shadow: inset 0 -2px 0 var(--tv-text-primary); }
    .tv-cms-link { color: var(--tv-text-primary); font-weight: 600; }
    .tv-cms-version__chip.is-current { box-shadow: inset 0 0 0 1px rgba(17, 24, 39, 0.25); }
</style>
