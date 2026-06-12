<style>
    :root {
        --st-graphite: var(--sukoon-text-primary, #111827);
        --st-graphite-hover: var(--sukoon-hover, #000000);
        --st-accent: var(--sukoon-button, #111827);
        --st-accent-soft: rgba(17, 24, 39, 0.08);
        --st-bg: var(--sukoon-bg-primary, #FFFFFF);
        --st-section: var(--sukoon-section, #F9FAFB);
        --st-border: var(--sukoon-border, #E5E7EB);
        --st-muted: var(--sukoon-text-secondary, #6B7280);
        --st-text-on-dark: var(--sukoon-text-on-dark, #FFFFFF);
        --st-shadow: var(--sukoon-shadow, 0 2px 12px rgba(15, 23, 42, 0.05));
        --st-radius: var(--sukoon-radius, 16px);
    }

    .st-admin {
        color: var(--st-graphite);
        font-size: 0.9375rem;
    }

    .st-page-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .st-page-header__title {
        margin: 0;
        font-size: 1.375rem;
        font-weight: 700;
        letter-spacing: -0.02em;
    }

    .st-page-header__meta {
        color: var(--st-muted);
        font-size: 0.875rem;
    }

    .st-card {
        background: var(--st-bg);
        border: 1px solid var(--st-border);
        border-radius: var(--st-radius);
        box-shadow: var(--st-shadow);
        margin-bottom: 1.25rem;
    }

    .st-card__header {
        padding: 1rem 1.25rem;
        border-bottom: 1px solid var(--st-border);
        font-weight: 600;
    }

    .st-card__body {
        padding: 1.25rem;
    }

    .st-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 1rem;
    }

    .st-field label {
        display: block;
        font-size: 0.8125rem;
        font-weight: 600;
        margin-bottom: 0.35rem;
        color: var(--st-graphite);
    }

    .st-field input[type="text"],
    .st-field input[type="color"],
    .st-field select {
        width: 100%;
        border: 1px solid var(--st-border);
        border-radius: 12px;
        padding: 0.5rem 0.65rem;
        font-size: 0.875rem;
    }

    .st-field input[type="color"] {
        height: 42px;
        padding: 0.25rem;
        cursor: pointer;
    }

    .st-preset {
        border: 1px solid var(--st-border);
        border-radius: var(--st-radius);
        padding: 1rem;
        cursor: pointer;
        transition: border-color 0.15s, box-shadow 0.15s;
        background: var(--st-bg);
    }

    .st-preset:hover,
    .st-preset.is-active {
        border-color: var(--st-graphite);
        box-shadow: 0 0 0 1px rgba(17, 24, 39, 0.18);
    }

    .st-preset__swatches {
        display: flex;
        gap: 0.35rem;
        margin: 0.65rem 0;
    }

    .st-preset__swatch {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        border: 1px solid rgba(0, 0, 0, 0.08);
    }

    .st-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .st-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        padding: 0.55rem 1rem;
        border-radius: 12px;
        font-size: 0.875rem;
        font-weight: 600;
        border: 1px solid transparent;
        cursor: pointer;
        text-decoration: none;
    }

    .st-btn--primary {
        background: var(--st-graphite);
        color: var(--st-text-on-dark);
    }

    .st-btn--primary:hover {
        background: var(--st-graphite-hover);
        color: var(--st-text-on-dark);
    }

    .st-btn--accent {
        background: var(--st-graphite-hover);
        color: var(--st-text-on-dark);
    }

    .st-btn--accent:hover {
        background: var(--st-graphite);
        color: var(--st-text-on-dark);
    }

    .st-btn--ghost {
        background: #fff;
        border-color: var(--st-border);
        color: var(--st-graphite);
    }

    .st-preview {
        border: 1px solid var(--st-border);
        border-radius: var(--st-radius);
        overflow: hidden;
        min-height: 420px;
    }

    .st-preview__chrome {
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid var(--preview-border, var(--st-border));
        background: var(--preview-sidebar, #111827);
        color: var(--preview-white, var(--st-text-on-dark, #FFFFFF));
    }

    .st-preview__body {
        padding: 1.25rem;
        background: var(--preview-section, #F9FAFB);
    }

    .st-preview__card {
        background: var(--preview-card, var(--st-bg, #fff));
        border: 1px solid var(--preview-border, #E5E7EB);
        border-radius: var(--preview-radius, 16px);
        box-shadow: var(--preview-shadow, 0 2px 12px rgba(15, 23, 42, 0.05));
        padding: 1.25rem;
        max-width: 360px;
    }

    .st-preview__btn {
        display: inline-block;
        margin-top: 0.75rem;
        padding: 0.55rem 1rem;
        border-radius: var(--preview-radius, 16px);
        background: var(--preview-button, #111827);
        color: var(--st-text-on-dark, #FFFFFF);
        font-weight: 600;
        font-size: 0.875rem;
        border: none;
    }

    .st-preview__btn:hover {
        background: var(--preview-hover, #000000);
    }

    .st-preview__link {
        color: var(--preview-link, #111827);
        font-weight: 600;
        text-decoration: none;
    }

    .st-preview__price {
        color: var(--preview-primary, #111827);
    }

    .st-badge {
        display: inline-flex;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        background: var(--st-accent-soft);
        color: var(--st-graphite);
    }

    .st-badge--draft {
        background: rgba(107, 114, 128, 0.12);
        color: #4B5563;
    }

    .st-version-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .st-version-list li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.65rem 0;
        border-bottom: 1px solid var(--st-border);
        font-size: 0.8125rem;
    }

    .st-version-list li:last-child {
        border-bottom: none;
    }

    @media (max-width: 991px) {
        .st-layout {
            flex-direction: column;
        }
    }
</style>
