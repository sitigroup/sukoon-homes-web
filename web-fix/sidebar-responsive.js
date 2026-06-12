/**
 * Sidebar helper for Sukoon admin — works WITH Mazer (app.js), not against it.
 * - Burger menu: handled only by Mazer (no double-toggle).
 * - Overlay, close button, escape, and tablet "stay closed" preference.
 */
(function () {
    'use strict';

    var DESKTOP_MIN = 1200;
    var STORAGE_KEY = 'sukoon_admin_sidebar_closed';
    var initialized = false;

    function isDesktop() {
        return window.innerWidth >= DESKTOP_MIN;
    }

    function isCollapsible() {
        return window.innerWidth < DESKTOP_MIN;
    }

    function readClosedPreference() {
        try {
            return sessionStorage.getItem(STORAGE_KEY) === '1';
        } catch (e) {
            return false;
        }
    }

    function saveClosedPreference(closed) {
        if (isDesktop()) {
            return;
        }
        try {
            if (closed) {
                sessionStorage.setItem(STORAGE_KEY, '1');
            } else {
                sessionStorage.removeItem(STORAGE_KEY);
            }
        } catch (e) {
            /* ignore */
        }
    }

    function hideSidebar() {
        if (window.sidebar && typeof window.sidebar.hide === 'function') {
            window.sidebar.hide();
            return;
        }
        var sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.classList.remove('active');
        }
    }

    function syncUiFromSidebar() {
        var sidebar = document.getElementById('sidebar');
        var sidebarOverlay = document.getElementById('sidebarOverlay');
        if (!sidebar) {
            return;
        }

        var active = sidebar.classList.contains('active');

        if (isDesktop()) {
            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('active');
            }
            document.body.style.overflow = '';
            return;
        }

        if (active) {
            saveClosedPreference(false);
            if (sidebarOverlay) {
                sidebarOverlay.classList.add('active');
            }
            document.body.style.overflow = 'hidden';
        } else {
            if (sidebarOverlay) {
                sidebarOverlay.classList.remove('active');
            }
            document.body.style.overflow = '';
        }
    }

    function initSidebar() {
        if (initialized) {
            return;
        }

        var sidebar = document.getElementById('sidebar');
        var sidebarOverlay = document.getElementById('sidebarOverlay');

        if (!sidebar) {
            return;
        }

        initialized = true;

        /* Watch Mazer toggling #sidebar.active */
        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function () {
                var active = sidebar.classList.contains('active');
                if (!active && isCollapsible()) {
                    saveClosedPreference(true);
                }
                syncUiFromSidebar();
            });
            observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
        }

        /* Close button + overlay — use Mazer hide when available */
        document.addEventListener('click', function (e) {
            if (e.target.closest('.sidebar-close-btn')) {
                e.preventDefault();
                e.stopPropagation();
                saveClosedPreference(true);
                hideSidebar();
            }

            if (sidebarOverlay && e.target === sidebarOverlay) {
                e.preventDefault();
                saveClosedPreference(true);
                hideSidebar();
            }
        });

        /* Close after real navigation (mobile + iPad) */
        document.addEventListener('click', function (e) {
            if (!isCollapsible()) {
                return;
            }

            var link = e.target.closest('#sidebarMenu a');
            if (!link) {
                return;
            }

            var href = link.getAttribute('href');
            var sidebarItem = link.closest('.sidebar-item');

            if (href === '#' && sidebarItem && sidebarItem.classList.contains('has-sub')) {
                return;
            }

            if (href && href !== '#' && href !== '') {
                var currentUrl = window.location.href;
                setTimeout(function () {
                    if (window.location.href !== currentUrl) {
                        saveClosedPreference(true);
                        hideSidebar();
                    }
                }, 250);
            }
        });

        window.addEventListener('resize', function () {
            if (isDesktop()) {
                sidebar.classList.add('active');
                saveClosedPreference(false);
                syncUiFromSidebar();
            } else if (readClosedPreference()) {
                sidebar.classList.remove('active');
                syncUiFromSidebar();
            } else {
                syncUiFromSidebar();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('active') && isCollapsible()) {
                saveClosedPreference(true);
                hideSidebar();
            }
        });

        /* Run after Mazer onFirstLoad (same tick + next frame) */
        function applyTabletPreference() {
            if (isDesktop()) {
                sidebar.classList.add('active');
            } else if (readClosedPreference()) {
                sidebar.classList.remove('active');
            }
            syncUiFromSidebar();
        }

        setTimeout(applyTabletPreference, 0);
        setTimeout(applyTabletPreference, 150);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebar);
    } else {
        initSidebar();
    }
})();
