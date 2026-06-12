<style>
    /* Order detail — monochrome ops dashboard (scoped .tv-order-detail-page) */
    .tv-order-detail-page {
        --tv-od-black: #111827;
        --tv-od-graphite: #1F2937;
        --tv-od-border: #E5E7EB;
        --tv-od-bg: #F9FAFB;
        --tv-od-card: #FFFFFF;
        --tv-od-muted: #6B7280;
        --tv-od-shadow: 0 2px 12px rgba(17, 24, 39, 0.06);
        --tv-od-radius: 16px;
        max-width: 100%;
        width: 100%;
        overflow: visible;
        position: relative;
    }

    /* Dashboard stack — summary + body in normal document flow (no float overlap) */
    .tv-order-detail-page .tv-od-dashboard {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        width: 100%;
        position: relative;
        overflow: visible;
    }
    .tv-order-detail-page .tv-od-summary-wrap {
        position: relative;
        width: 100%;
        flex: 0 0 auto;
    }
    .tv-order-detail-page .tv-od-dashboard-body {
        position: relative;
        width: 100%;
        min-width: 0;
        flex: 1 1 auto;
        z-index: 0;
        overflow: visible;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .tv-order-detail-page .tv-detail-grid {
        display: grid !important;
        grid-template-columns: minmax(0, 2fr) minmax(0, 3fr) !important;
        gap: 1.25rem;
        align-items: start;
        width: 100%;
        position: relative;
        overflow: visible;
    }
    .tv-order-detail-page .tv-od-col {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        min-width: 0;
        position: relative;
        overflow: visible;
    }
    .tv-order-detail-page .tv-od-col--left,
    .tv-order-detail-page .tv-od-col--right {
        align-self: start;
    }

    @media (max-width: 1023.98px) {
        .tv-order-detail-page .tv-detail-grid {
            grid-template-columns: 1fr !important;
        }
    }

    /* Summary card — normal document flow (no overlay on sections below) */
    .tv-order-detail-page .tv-od-summary {
        position: relative;
        z-index: auto;
        background: var(--tv-od-card);
        border: 1px solid var(--tv-od-border);
        border-radius: var(--tv-od-radius);
        box-shadow: var(--tv-od-shadow);
        padding: 1.25rem 1.5rem;
        margin: 0;
        width: 100%;
        box-sizing: border-box;
    }
    .tv-order-detail-page .tv-alert-privacy {
        border-left: 3px solid var(--tv-od-graphite) !important;
        background: var(--tv-od-bg) !important;
        border-radius: 12px !important;
    }
    .tv-od-summary__top {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .tv-od-summary__order {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--tv-od-black);
        letter-spacing: -0.02em;
        margin: 0 0 0.35rem;
    }
    .tv-od-summary__package {
        font-size: 1.125rem;
        font-weight: 600;
        color: var(--tv-od-graphite);
        margin: 0 0 0.25rem;
    }
    .tv-od-summary__meta-line {
        font-size: 0.9375rem;
        color: var(--tv-od-muted);
        margin: 0;
    }
    .tv-od-summary__price {
        font-size: 1.375rem;
        font-weight: 700;
        color: var(--tv-od-black);
        white-space: nowrap;
    }
    .tv-od-summary__chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }
    .tv-od-summary__score {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
        padding-top: 1rem;
        border-top: 1px solid var(--tv-od-border);
        font-size: 0.9375rem;
        color: var(--tv-od-graphite);
    }
    .tv-od-summary__score strong {
        font-size: 1.0625rem;
        font-weight: 700;
        color: var(--tv-od-black);
    }

    .tv-order-detail-page .tv-od-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.3rem 0.7rem;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        line-height: 1.2;
    }
    .tv-od-chip--success { background: #F3F4F6; color: var(--tv-od-black); border: 1px solid var(--tv-od-border); }
    .tv-od-chip--warning { background: #F3F4F6; color: var(--tv-od-graphite); border: 1px solid #D1D5DB; }
    .tv-od-chip--danger { background: #111827; color: #fff; border: 1px solid #111827; }
    .tv-od-chip--muted { background: #F9FAFB; color: var(--tv-od-muted); border: 1px solid var(--tv-od-border); }
    .tv-od-chip--dark { background: var(--tv-od-black); color: #fff; border: 1px solid var(--tv-od-black); }

    /* Premium accordion — overflow hidden only for rounded corners */
    .tv-order-detail-page .tv-od-acc {
        background: var(--tv-od-card);
        border: 1px solid var(--tv-od-border);
        border-radius: var(--tv-od-radius);
        box-shadow: var(--tv-od-shadow);
        margin: 0;
        position: relative;
        overflow: hidden;
        flex: 0 0 auto;
    }
    .tv-order-detail-page .tv-od-acc:not([open]) .tv-od-acc__body {
        display: none;
    }
    .tv-order-detail-page .tv-od-audit-section {
        width: 100%;
        margin-top: 0.25rem;
    }
    .tv-od-acc > summary {
        list-style: none;
        cursor: pointer;
        display: grid;
        grid-template-columns: auto 1fr auto auto;
        align-items: center;
        gap: 0.85rem;
        padding: 1rem 1.25rem;
        user-select: none;
    }
    .tv-od-acc > summary::-webkit-details-marker { display: none; }
    .tv-od-acc__icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 12px;
        background: var(--tv-od-bg);
        border: 1px solid var(--tv-od-border);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.125rem;
        color: var(--tv-od-graphite);
    }
    .tv-od-acc__title {
        display: block;
        font-size: 1rem;
        font-weight: 700;
        color: var(--tv-od-black);
        line-height: 1.3;
    }
    .tv-od-acc__helper {
        display: block;
        font-size: 0.875rem;
        font-weight: 400;
        color: var(--tv-od-muted);
        margin-top: 0.15rem;
    }
    .tv-od-acc__meta {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--tv-od-muted);
        text-align: right;
    }
    .tv-od-acc__chevron {
        width: 0.55rem;
        height: 0.55rem;
        border-right: 2px solid var(--tv-od-muted);
        border-bottom: 2px solid var(--tv-od-muted);
        transform: rotate(45deg);
        transition: transform 0.15s;
    }
    .tv-od-acc[open] > summary {
        border-bottom: 1px solid var(--tv-od-border);
        background: var(--tv-od-bg);
    }
    .tv-od-acc[open] > summary .tv-od-acc__chevron {
        transform: rotate(-135deg);
        margin-top: 0.2rem;
    }
    .tv-od-acc__body {
        padding: 1.25rem;
    }
    .tv-od-acc--muted .tv-od-acc__title { font-size: 0.9375rem; color: var(--tv-od-graphite); }

    /* Typography & forms */
    .tv-order-detail-page .tv-od-section-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--tv-od-black);
        margin: 0 0 1rem;
    }
    .tv-order-detail-page .form-label,
    .tv-order-detail-page .tv-od-label {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--tv-od-graphite);
        margin-bottom: 0.4rem;
    }
    .tv-order-detail-page .tv-od-input,
    .tv-order-detail-page .form-control,
    .tv-order-detail-page .form-select {
        font-size: 0.875rem !important;
        min-height: 44px !important;
        padding: 0.5rem 0.85rem !important;
        border: 1px solid var(--tv-od-border) !important;
        border-radius: 10px !important;
        color: var(--tv-od-black) !important;
        background: var(--tv-od-card) !important;
    }
    .tv-order-detail-page textarea.tv-od-input,
    .tv-order-detail-page textarea.form-control {
        min-height: 110px !important;
        height: auto !important;
    }
    .tv-order-detail-page .tv-od-form-row {
        --bs-gutter-y: 1rem;
    }
    .tv-order-detail-page .tv-od-field-group {
        margin-bottom: 1rem;
    }
    .tv-order-detail-page .tv-od-field-group:last-child { margin-bottom: 0; }

    .tv-order-detail-page .tv-od-btn-save,
    .tv-order-detail-page .btn.tv-od-btn-save {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 44px !important;
        padding: 0.65rem 1.35rem !important;
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        border-radius: 10px !important;
        background: var(--tv-od-black) !important;
        border: 1px solid var(--tv-od-black) !important;
        color: #fff !important;
        box-shadow: var(--tv-od-shadow) !important;
    }
    .tv-order-detail-page .tv-od-btn-save:hover {
        background: #000 !important;
        border-color: #000 !important;
        color: #fff !important;
    }
    .tv-order-detail-page .tv-od-btn-secondary,
    .tv-order-detail-page .btn.tv-od-btn-secondary {
        min-height: 44px !important;
        padding: 0.55rem 1.1rem !important;
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        border-radius: 10px !important;
        background: var(--tv-od-card) !important;
        border: 1px solid var(--tv-od-border) !important;
        color: var(--tv-od-black) !important;
    }
    .tv-order-detail-page .tv-od-btn-secondary:hover {
        background: var(--tv-od-bg) !important;
        border-color: #D1D5DB !important;
    }
    .tv-order-detail-page .tv-od-btn-action {
        min-height: 44px !important;
        padding: 0.55rem 1rem !important;
        font-size: 0.8125rem !important;
        font-weight: 600 !important;
        border-radius: 10px !important;
        background: var(--tv-od-card) !important;
        border: 1px solid var(--tv-od-border) !important;
        color: var(--tv-od-black) !important;
    }
    .tv-order-detail-page .tv-od-btn-action:hover {
        background: var(--tv-od-black) !important;
        color: #fff !important;
        border-color: var(--tv-od-black) !important;
    }

    /* Document cards */
    .tv-od-doc-card {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.15rem;
        border: 1px solid var(--tv-od-border);
        border-radius: 14px;
        background: var(--tv-od-bg);
        margin-bottom: 0.75rem;
    }
    .tv-od-doc-card:last-child { margin-bottom: 0; }
    .tv-od-doc-card__title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--tv-od-black);
        margin: 0 0 0.25rem;
    }
    .tv-od-doc-card__meta {
        font-size: 0.875rem;
        color: var(--tv-od-muted);
        margin: 0;
    }
    .tv-od-doc-card__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    /* Reference cards */
    .tv-od-ref-card {
        border: 1px solid var(--tv-od-border);
        border-radius: var(--tv-od-radius);
        background: var(--tv-od-card);
        padding: 1.25rem;
        margin-bottom: 1rem;
    }
    .tv-od-ref-card:last-child { margin-bottom: 0; }
    .tv-od-ref-card__head {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--tv-od-border);
    }
    .tv-od-ref-card__type {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--tv-od-black);
        margin: 0 0 0.35rem;
    }
    .tv-od-ref-card__mobile {
        font-size: 0.9375rem;
        color: var(--tv-od-graphite);
        margin: 0;
    }
    .tv-od-ref-kv {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 0.75rem 1rem;
        margin-bottom: 1rem;
        font-size: 0.875rem;
    }
    .tv-od-ref-kv dt {
        font-weight: 600;
        color: var(--tv-od-muted);
        margin: 0 0 0.2rem;
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .tv-od-ref-kv dd { margin: 0; color: var(--tv-od-black); font-weight: 500; }

    /* Police workflow — Task 15B polish */
    .tv-od-police-workflow {
        border: 1px solid var(--tv-od-border);
        border-radius: var(--tv-od-radius);
        background: var(--tv-od-card);
        overflow: hidden;
        box-shadow: 0 2px 14px rgba(17, 24, 39, 0.04);
    }
    .tv-od-police-workflow__head {
        padding: 1.25rem 1.35rem;
        background: linear-gradient(180deg, #fafbfc 0%, var(--tv-od-bg) 100%);
        border-bottom: 1px solid var(--tv-od-border);
    }
    .tv-od-police-workflow__head-top {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.65rem;
        margin-bottom: 0.85rem;
    }
    .tv-od-police-rajasthan-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .tv-od-police-workflow__head-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 0.85rem 1.25rem;
    }
    .tv-od-police-workflow__head-grid dt {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--tv-od-muted);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin: 0 0 0.25rem;
    }
    .tv-od-police-workflow__head-grid dd {
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--tv-od-black);
        margin: 0;
        line-height: 1.35;
    }
    .tv-od-empty-value {
        color: var(--tv-od-muted) !important;
        font-weight: 500 !important;
        font-style: italic;
    }
    .tv-od-police-timeline {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem 0.5rem;
        padding: 1rem 1.35rem;
        background: var(--tv-od-card);
        border-bottom: 1px solid var(--tv-od-border);
    }
    .tv-od-police-timeline__step {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--tv-od-muted);
    }
    .tv-od-police-timeline__step.is-done {
        color: var(--tv-od-black);
    }
    .tv-od-police-timeline__step.is-active {
        color: var(--tv-od-black);
    }
    .tv-od-police-timeline__step.is-active .tv-od-police-timeline__dot {
        background: var(--tv-od-black);
        box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.12);
    }
    .tv-od-police-timeline__step.is-done .tv-od-police-timeline__dot {
        background: #047857;
    }
    .tv-od-police-timeline__step.is-rejected .tv-od-police-timeline__dot {
        background: #b91c1c;
    }
    .tv-od-police-timeline__dot {
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 50%;
        background: #d1d5db;
        flex-shrink: 0;
    }
    .tv-od-police-timeline__connector {
        width: 1.25rem;
        height: 2px;
        background: #e5e7eb;
        border-radius: 1px;
        flex-shrink: 0;
    }
    .tv-od-police-timeline__step.is-done + .tv-od-police-timeline__connector {
        background: #a7f3d0;
    }
    .tv-od-police-actions {
        padding: 1rem 1.35rem 1.1rem;
        background: var(--tv-od-bg);
        border-bottom: 1px solid var(--tv-od-border);
    }
    .tv-od-police-actions__label {
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: var(--tv-od-muted);
        margin-bottom: 0.55rem;
    }
    .tv-od-police-actions__buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .tv-od-police-actions__hint {
        margin: 0.55rem 0 0;
        font-size: 0.8125rem;
        color: var(--tv-od-muted);
    }
    .tv-od-police-actions .btn.is-current {
        box-shadow: inset 0 0 0 2px rgba(17, 24, 39, 0.15);
    }
    .tv-od-police-reject-btn {
        margin-left: auto;
    }
    .tv-od-police-body {
        padding: 1.35rem 1.35rem 1.5rem;
    }
    .tv-od-police-body .row { --bs-gutter-y: 1rem; }
    .tv-od-police-advanced {
        border: 1px dashed var(--tv-od-border);
        border-radius: 12px;
        background: #fafbfc;
        padding: 0.65rem 1rem 1rem;
    }
    .tv-od-police-advanced__summary {
        cursor: pointer;
        font-size: 0.8125rem;
        font-weight: 700;
        color: var(--tv-od-muted);
        list-style: none;
    }
    .tv-od-police-advanced__summary::-webkit-details-marker { display: none; }
    .tv-od-police-advanced__body {
        margin-top: 0.85rem;
    }
    .tv-od-field-hint {
        font-size: 0.8125rem;
        color: var(--tv-od-muted);
        margin-top: 0.35rem;
    }
    .tv-od-police-upload-card {
        display: flex;
        gap: 1rem;
        height: 100%;
        padding: 1.1rem 1.15rem;
        border: 1px solid var(--tv-od-border);
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 2px 12px rgba(17, 24, 39, 0.04);
    }
    .tv-od-police-upload-card__icon {
        flex-shrink: 0;
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 12px;
        background: #ecfdf5;
        color: #047857;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    .tv-od-police-upload-card__icon--cert {
        background: #eff6ff;
        color: #1d4ed8;
    }
    .tv-od-police-upload-card__body {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }
    .tv-od-police-upload-card__title {
        margin: 0;
        font-size: 0.9375rem;
        font-weight: 700;
        color: var(--tv-od-black);
    }
    .tv-od-police-upload-card__meta {
        margin: 0;
        font-size: 0.8125rem;
        color: var(--tv-od-muted);
    }
    .tv-od-police-upload-card__link {
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--tv-od-black);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    .tv-od-police-upload-card__link:hover { text-decoration: underline; }
    .tv-od-police-upload-card__empty {
        font-size: 0.8125rem;
        color: var(--tv-od-muted);
        font-style: italic;
    }
    .tv-od-police-upload-card__file {
        margin-top: 0.35rem;
        margin-bottom: 0;
    }
    .tv-od-police-upload-card__input {
        position: absolute;
        width: 0.1px;
        height: 0.1px;
        opacity: 0;
        overflow: hidden;
        z-index: -1;
    }
    .tv-od-police-save-row {
        padding-top: 0.25rem;
    }

    /* Checks table in dashboard */
    .tv-od-checks-table thead th {
        font-size: 0.8125rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--tv-od-muted);
        background: var(--tv-od-bg);
        padding: 0.85rem 0.75rem;
        border-bottom: 1px solid var(--tv-od-border);
    }
    .tv-od-checks-table tbody td {
        padding: 0.85rem 0.75rem;
        vertical-align: middle;
        font-size: 0.875rem;
    }
    .tv-od-checks-table .form-select { min-height: 44px !important; }

    .tv-od-dl {
        display: grid;
        gap: 0.65rem;
        font-size: 0.875rem;
    }
    .tv-od-dl__row {
        display: grid;
        grid-template-columns: 8.5rem 1fr;
        gap: 0.35rem 1rem;
    }
    .tv-od-dl__row dt { font-weight: 600; color: var(--tv-od-muted); margin: 0; }
    .tv-od-dl__row dd { margin: 0; color: var(--tv-od-black); }

    .tv-od-link-back {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--tv-od-black);
        text-decoration: none;
        margin-bottom: 1rem;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .tv-od-link-back:hover { text-decoration: underline; }

    /* Responsive — no overlap at common breakpoints */
    @media (min-width: 1366px) {
        .tv-order-detail-page .tv-od-dashboard { gap: 1.5rem; }
        .tv-order-detail-page .tv-detail-grid { gap: 1.5rem; }
    }
    @media (max-width: 768px) {
        .tv-order-detail-page .tv-od-summary { padding: 1rem 1.15rem; }
        .tv-order-detail-page .tv-od-acc > summary {
            grid-template-columns: auto 1fr auto;
            gap: 0.65rem;
        }
        .tv-order-detail-page .tv-od-acc__meta { display: none; }
        .tv-order-detail-page .tv-od-dl__row {
            grid-template-columns: 1fr;
        }
    }
    @media (max-width: 768px) {
        .tv-od-police-timeline {
            flex-direction: column;
            align-items: flex-start;
        }
        .tv-od-police-timeline__connector {
            width: 2px;
            height: 0.75rem;
            margin-left: 0.2rem;
        }
        .tv-od-police-upload-card {
            flex-direction: column;
        }
        .tv-od-police-reject-btn {
            margin-left: 0;
            width: 100%;
        }
    }
    @media (max-width: 375px) {
        .tv-order-detail-page .tv-od-summary__price { font-size: 1.15rem; }
        .tv-order-detail-page .tv-od-police-actions__buttons .btn { width: 100%; }
        .tv-od-police-workflow__head-top {
            flex-direction: column;
            align-items: stretch;
        }
        .tv-od-police-rajasthan-btn { justify-content: center; }
        .tv-od-police-workflow__head,
        .tv-od-police-body,
        .tv-od-police-actions,
        .tv-od-police-timeline {
            padding-left: 1rem;
            padding-right: 1rem;
        }
    }
</style>
