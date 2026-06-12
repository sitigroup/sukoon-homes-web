@if (!empty($sukoonPublishedTheme['css_variables']))
<script>document.documentElement.dataset.sukoonThemeActive = 'true';</script>
<style id="sukoon-admin-theme-vars">
    :root {
        @foreach($sukoonPublishedTheme['css_variables'] as $var => $value)
        {{ $var }}: {{ $value }};
        @endforeach
    }

    /* Sukoon Theme — admin contrast-safe overrides (plugin-only) */
    body {
        color: var(--sukoon-text-primary, #111827);
        background-color: var(--sukoon-section, #F9FAFB);
    }

    .card,
    .modal-content,
    .dropdown-menu {
        background-color: var(--sukoon-bg-primary, #FFFFFF) !important;
        color: var(--sukoon-text-primary, #111827) !important;
        border-color: var(--sukoon-border, #E5E7EB);
        border-radius: var(--sukoon-radius, 16px);
        box-shadow: var(--sukoon-shadow, 0 4px 20px rgba(0,0,0,0.08));
    }

    .card-header,
    .card-body,
    .card-title,
    .table,
    .table td,
    .table th {
        color: var(--sukoon-text-primary, #111827);
    }

    .text-muted,
    .form-text,
    small {
        color: var(--sukoon-text-secondary, #6B7280) !important;
    }

    .sidebar-wrapper,
    .sidebar {
        background: var(--sukoon-bg-dark, #111827) !important;
        color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    .sidebar-wrapper a,
    .sidebar a {
        color: var(--sukoon-link-on-dark, #FFFFFF) !important;
    }

    .btn-primary {
        background-color: var(--sukoon-button, #111827) !important;
        border-color: var(--sukoon-button, #111827) !important;
        color: var(--sukoon-button-text, #FFFFFF) !important;
    }

    .btn-primary:hover {
        background-color: var(--sukoon-hover, #000000) !important;
        border-color: var(--sukoon-hover, #000000) !important;
        color: var(--sukoon-button-hover-text, #FFFFFF) !important;
    }

    .btn-secondary,
    .btn-outline,
    .btn-light {
        background-color: var(--sukoon-bg-primary, #FFFFFF) !important;
        color: var(--sukoon-text-primary, #111827) !important;
        border-color: var(--sukoon-border, #E5E7EB) !important;
    }

    a:not(.btn):not(.nav-link):not(.page-link) {
        color: var(--sukoon-link-on-light, #111827);
    }

    .sidebar-wrapper a:not(.btn),
    .sidebar a:not(.btn) {
        color: var(--sukoon-link-on-dark, #FFFFFF) !important;
    }

    input.form-control,
    select.form-select,
    textarea.form-control {
        background-color: var(--sukoon-bg-primary, #FFFFFF) !important;
        color: var(--sukoon-text-primary, #111827) !important;
        border-color: var(--sukoon-border, #E5E7EB) !important;
    }

    .table thead th {
        background-color: var(--sukoon-section, #F9FAFB);
        color: var(--sukoon-text-primary, #111827);
    }

    .pagination .page-link {
        background-color: var(--sukoon-bg-primary, #FFFFFF);
        color: var(--sukoon-text-primary, #111827);
        border-color: var(--sukoon-border, #E5E7EB);
    }

    .pagination .page-item.active .page-link {
        background-color: var(--sukoon-button, #111827);
        color: var(--sukoon-button-text, #FFFFFF);
        border-color: var(--sukoon-button, #111827);
    }

    :disabled,
    .disabled {
        color: var(--sukoon-text-secondary, #6B7280) !important;
        opacity: 0.72;
    }

    .badge:not(.bg-primary):not(.bg-dark) {
        background-color: var(--sukoon-section, #F9FAFB) !important;
        color: var(--sukoon-text-primary, #111827) !important;
    }

    /* Trust Verification admin (CMS, orders, packages, cities, automation, sample reports) */
    .tv-admin .card,
    .tv-admin .tv-card {
        background-color: var(--sukoon-bg-primary, #FFFFFF) !important;
        color: var(--sukoon-text-primary, #111827) !important;
    }

    .tv-admin .text-muted {
        color: var(--sukoon-text-secondary, #6B7280) !important;
    }

    .st-admin,
    .st-admin .st-card {
        color: var(--sukoon-text-primary, #111827);
    }
</style>
@endif
