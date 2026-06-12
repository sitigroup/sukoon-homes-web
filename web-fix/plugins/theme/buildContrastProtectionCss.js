/**
 * Sukoon Theme — global contrast-safe CSS (injected when theme is published).
 * Plugin-only; uses semantic tokens + surface rules.
 */
export function buildContrastProtectionCss() {
  return `
    /* ── Base page (light surface) ── */
    html[data-sukoon-theme-active="true"] body {
      color: var(--sukoon-text-primary, #111827);
      background-color: var(--sukoon-section, #F9FAFB);
    }

    /* ── Light cards / panels ── */
    html[data-sukoon-theme-active="true"] .card,
    html[data-sukoon-theme-active="true"] .sukoon-card,
    html[data-sukoon-theme-active="true"] [class*="Card"]:not([class*="CardHeader"]),
    html[data-sukoon-theme-active="true"] .property-card,
    html[data-sukoon-theme-active="true"] .project-card,
    html[data-sukoon-theme-active="true"] .modal-content,
    html[data-sukoon-theme-active="true"] .dropdown-menu,
    html[data-sukoon-theme-active="true"] .st-preview__card {
      background-color: var(--sukoon-bg-primary, var(--sukoon-card, #FFFFFF)) !important;
      color: var(--sukoon-text-primary, #111827) !important;
      border-color: var(--sukoon-border, #E5E7EB);
    }

  html[data-sukoon-theme-active="true"] .card .card-body,
    html[data-sukoon-theme-active="true"] .card .card-header,
    html[data-sukoon-theme-active="true"] .card .card-title,
    html[data-sukoon-theme-active="true"] .card p,
    html[data-sukoon-theme-active="true"] .card span:not(.badge):not([class*="badge"]),
    html[data-sukoon-theme-active="true"] .property-card *,
    html[data-sukoon-theme-active="true"] main {
      color: inherit;
    }

    html[data-sukoon-theme-active="true"] .text-muted,
    html[data-sukoon-theme-active="true"] .sukoon-muted,
    html[data-sukoon-theme-active="true"] small,
    html[data-sukoon-theme-active="true"] .form-text {
      color: var(--sukoon-text-secondary, #6B7280) !important;
    }

    /* ── Dark surfaces ── */
    html[data-sukoon-theme-active="true"] .sukoon-surface-dark,
    html[data-sukoon-theme-active="true"] .sidebar-wrapper,
    html[data-sukoon-theme-active="true"] .sidebar,
    html[data-sukoon-theme-active="true"] .st-preview__chrome,
    html[data-sukoon-theme-active="true"] footer.footer-dark,
    html[data-sukoon-theme-active="true"] [class*="bg-primaryColor"],
    html[data-sukoon-theme-active="true"] [class*="bg-primary"]:not(.card):not(.btn-light) {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    html[data-sukoon-theme-active="true"] .sidebar-wrapper a,
    html[data-sukoon-theme-active="true"] .sidebar a,
    html[data-sukoon-theme-active="true"] .sukoon-surface-dark a,
    html[data-sukoon-theme-active="true"] .st-preview__chrome a {
      color: var(--sukoon-link-on-dark, var(--sukoon-text-on-dark, #FFFFFF)) !important;
    }

    /* ── Primary buttons (dark fill → light text) ── */
    html[data-sukoon-theme-active="true"] .btn-primary,
    html[data-sukoon-theme-active="true"] .sukoon-btn,
    html[data-sukoon-theme-active="true"] button[type="submit"]:not(.btn-outline):not(.btn-light):not(.btn-secondary),
    html[data-sukoon-theme-active="true"] .st-preview__btn,
    html[data-sukoon-theme-active="true"] .st-btn--primary,
    html[data-sukoon-theme-active="true"] .st-btn--accent {
      background-color: var(--sukoon-button, #111827) !important;
      border-color: var(--sukoon-button, #111827) !important;
      color: var(--sukoon-button-text, var(--sukoon-text-on-dark, #FFFFFF)) !important;
    }

    html[data-sukoon-theme-active="true"] .btn-primary:hover,
    html[data-sukoon-theme-active="true"] .sukoon-btn:hover,
    html[data-sukoon-theme-active="true"] .st-preview__btn:hover,
    html[data-sukoon-theme-active="true"] .st-btn--primary:hover,
    html[data-sukoon-theme-active="true"] .st-btn--accent:hover {
      background-color: var(--sukoon-hover, #000000) !important;
      border-color: var(--sukoon-hover, #000000) !important;
      color: var(--sukoon-button-hover-text, var(--sukoon-text-on-dark, #FFFFFF)) !important;
    }

    /* ── Secondary / outline / ghost buttons (light surface) ── */
    html[data-sukoon-theme-active="true"] .btn-secondary,
    html[data-sukoon-theme-active="true"] .btn-outline,
    html[data-sukoon-theme-active="true"] .btn-light,
    html[data-sukoon-theme-active="true"] .btn-ghost,
    html[data-sukoon-theme-active="true"] .st-btn--ghost {
      background-color: var(--sukoon-bg-primary, #FFFFFF) !important;
      color: var(--sukoon-text-primary, #111827) !important;
      border-color: var(--sukoon-border, #E5E7EB) !important;
    }

    html[data-sukoon-theme-active="true"] .btn-secondary:hover,
    html[data-sukoon-theme-active="true"] .btn-outline:hover,
    html[data-sukoon-theme-active="true"] .btn-light:hover,
    html[data-sukoon-theme-active="true"] .st-btn--ghost:hover {
      background-color: var(--sukoon-section, #F9FAFB) !important;
      color: var(--sukoon-text-primary, #111827) !important;
    }

    /* ── Links on light backgrounds ── */
    html[data-sukoon-theme-active="true"] a:not(.btn):not([class*="btn-"]):not(.nav-link):not(.page-link) {
      color: var(--sukoon-link-on-light, var(--sukoon-link, #111827));
    }

    html[data-sukoon-theme-active="true"] .st-preview__link {
      color: var(--sukoon-link-on-light, #111827) !important;
    }

    html[data-sukoon-theme-active="true"] .st-preview__price {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    /* ── Navbar / header ── */
    html[data-sukoon-theme-active="true"] header,
    html[data-sukoon-theme-active="true"] nav.navbar,
    html[data-sukoon-theme-active="true"] .navbar {
      color: var(--sukoon-text-primary, #111827);
    }

    /* ── Forms ── */
    html[data-sukoon-theme-active="true"] input:not([type="checkbox"]):not([type="radio"]),
    html[data-sukoon-theme-active="true"] select,
    html[data-sukoon-theme-active="true"] textarea,
    html[data-sukoon-theme-active="true"] .form-control,
    html[data-sukoon-theme-active="true"] .form-select {
      background-color: var(--sukoon-bg-primary, #FFFFFF) !important;
      color: var(--sukoon-text-primary, #111827) !important;
      border-color: var(--sukoon-border, #E5E7EB) !important;
    }

    html[data-sukoon-theme-active="true"] input::placeholder,
    html[data-sukoon-theme-active="true"] textarea::placeholder {
      color: var(--sukoon-placeholder, #9CA3AF) !important;
      opacity: 1;
    }

    html[data-sukoon-theme-active="true"] label,
    html[data-sukoon-theme-active="true"] .form-label {
      color: var(--sukoon-text-primary, #111827);
    }

    /* ── Tables (admin + frontend) ── */
    html[data-sukoon-theme-active="true"] .table,
    html[data-sukoon-theme-active="true"] table {
      color: var(--sukoon-text-primary, #111827);
    }

    html[data-sukoon-theme-active="true"] .table thead th,
    html[data-sukoon-theme-active="true"] table thead th {
      background-color: var(--sukoon-section, #F9FAFB);
      color: var(--sukoon-text-primary, #111827);
    }

    html[data-sukoon-theme-active="true"] .table-striped tbody tr:nth-of-type(odd) {
      background-color: var(--sukoon-section, #F9FAFB);
    }

    /* ── Pagination ── */
    html[data-sukoon-theme-active="true"] .pagination .page-link {
      background-color: var(--sukoon-bg-primary, #FFFFFF);
      color: var(--sukoon-text-primary, #111827);
      border-color: var(--sukoon-border, #E5E7EB);
    }

    html[data-sukoon-theme-active="true"] .pagination .page-item.active .page-link {
      background-color: var(--sukoon-button, #111827);
      border-color: var(--sukoon-button, #111827);
      color: var(--sukoon-button-text, #FFFFFF);
    }

    html[data-sukoon-theme-active="true"] .pagination .page-item.disabled .page-link {
      color: var(--sukoon-text-secondary, #6B7280);
      background-color: var(--sukoon-section, #F9FAFB);
    }

    /* ── Chips / badges ── */
    html[data-sukoon-theme-active="true"] .badge:not(.bg-primary):not(.bg-dark),
    html[data-sukoon-theme-active="true"] .st-badge:not(.st-badge--draft) {
      background-color: var(--sukoon-section, #F9FAFB) !important;
      color: var(--sukoon-text-primary, #111827) !important;
      border: 1px solid var(--sukoon-border, #E5E7EB);
    }

    html[data-sukoon-theme-active="true"] .st-badge--draft {
      background-color: var(--sukoon-section, #F9FAFB) !important;
      color: var(--sukoon-text-secondary, #6B7280) !important;
    }

    /* ── Disabled states ── */
    html[data-sukoon-theme-active="true"] :disabled,
    html[data-sukoon-theme-active="true"] .disabled,
    html[data-sukoon-theme-active="true"] [aria-disabled="true"] {
      color: var(--sukoon-text-secondary, #6B7280) !important;
      opacity: 0.72;
    }

    /* ── Footer (light default) ── */
    html[data-sukoon-theme-active="true"] footer,
    html[data-sukoon-theme-active="true"] .footer {
      background-color: var(--sukoon-section, #F9FAFB);
      color: var(--sukoon-text-primary, #111827);
    }

    html[data-sukoon-theme-active="true"] footer a,
    html[data-sukoon-theme-active="true"] .footer a {
      color: var(--sukoon-link-on-light, #111827);
    }

    /* ── Profile / user sidebar ── */
    html[data-sukoon-theme-active="true"] .user-sidebar,
    html[data-sukoon-theme-active="true"] [class*="UserRoot"],
    html[data-sukoon-theme-active="true"] [class*="user-"] .card {
      color: var(--sukoon-text-primary, #111827);
    }

    /* ── Trust Verification pages (theme layer only) ── */
    html[data-sukoon-theme-active="true"] [class*="verification"] .card,
    html[data-sukoon-theme-active="true"] [class*="Verification"] .card,
    html[data-sukoon-theme-active="true"] [class*="my-verification"] .card {
      background-color: var(--sukoon-bg-primary, #FFFFFF) !important;
      color: var(--sukoon-text-primary, #111827) !important;
    }

    /* ── Admin theme settings page ── */
    html[data-sukoon-theme-active="true"] .st-admin,
    html[data-sukoon-theme-active="true"] .st-admin .st-card {
      color: var(--sukoon-text-primary, #111827);
    }

    /* ── Hardcoded color reset (common WRTeam inline bypass) ── */
    html[data-sukoon-theme-active="true"] [style*="color: rgb(255, 255, 255)"].card,
    html[data-sukoon-theme-active="true"] [style*="color:#fff"].card,
    html[data-sukoon-theme-active="true"] [style*="color: white"].card,
    html[data-sukoon-theme-active="true"] [style*="color:#ffffff"].card {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    html[data-sukoon-theme-active="true"] [style*="color: rgb(0, 0, 0)"],
    html[data-sukoon-theme-active="true"] [style*="color:#000"],
    html[data-sukoon-theme-active="true"] [style*="color:#000000"] {
      color: var(--sukoon-text-primary, #111827) !important;
    }

    html[data-sukoon-theme-active="true"] .primaryBg [style*="color"],
    html[data-sukoon-theme-active="true"] .brandBg [style*="color"],
    html[data-sukoon-theme-active="true"] .sidebar-wrapper [style*="color"] {
      color: var(--sukoon-text-on-dark, #FFFFFF) !important;
    }

    @media (max-width: 768px) {
      html[data-sukoon-theme-active="true"] body,
      html[data-sukoon-theme-active="true"] .card,
      html[data-sukoon-theme-active="true"] .btn-primary {
        font-size: 15px;
      }
    }
  `;
}
