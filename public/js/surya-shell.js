(() => {
    'use strict';

    const start = () => {
        const shell = document.querySelector('[data-surya-workspace]');
        const sidebar = document.getElementById('surya-sidebar');
        const toggle = document.querySelector('[data-surya-sidebar-toggle]');
        if (!shell || !sidebar || !toggle || shell.dataset.sidebarReady) return;
        shell.dataset.sidebarReady = 'true';
        sidebar.querySelectorAll('.sidebar-link.active').forEach(link => link.setAttribute('aria-current', 'page'));

        const root = document.documentElement;
        const mobile = window.matchMedia('(max-width: 1100px)');
        let mapFullscreen = document.body.classList.contains('map-fullscreen-open');
        const drawerMode = () => mobile.matches || mapFullscreen;
        const close = document.getElementById('sidebar-close');
        const overlay = document.createElement('button');
        overlay.className = 'surya-sidebar-overlay';
        overlay.type = 'button';
        overlay.tabIndex = -1;
        overlay.setAttribute('aria-label', 'Tutup menu');
        shell.appendChild(overlay);

        const readCollapsed = () => {
            try { return localStorage.getItem('surya-sidebar-collapsed') === 'true'; }
            catch (_) { return false; }
        };
        const sync = () => {
            const visible = drawerMode()
                ? shell.classList.contains('sidebar-open')
                : !shell.classList.contains('sidebar-collapsed');
            sidebar.inert = !visible;
            sidebar.setAttribute('aria-hidden', String(!visible));
            toggle.setAttribute('aria-expanded', String(visible));
            document.body.classList.toggle('surya-menu-open', drawerMode() && visible);
        };
        const notifyLayout = () => {
            window.setTimeout(() => window.dispatchEvent(new Event('surya:layout')), 230);
        };
        const closeMobile = (restoreFocus = false) => {
            shell.classList.remove('sidebar-open');
            sync();
            if (restoreFocus) toggle.focus();
        };
        const applyViewport = () => {
            const restoreFocus = sidebar.contains(document.activeElement);
            shell.classList.remove('sidebar-open');
            shell.classList.toggle('sidebar-collapsed', !mobile.matches && readCollapsed());
            root.classList.remove('surya-sidebar-precollapsed');
            sync();
            if (restoreFocus) toggle.focus();
            notifyLayout();
        };

        applyViewport();
        toggle.addEventListener('click', () => {
            if (drawerMode()) {
                shell.classList.toggle('sidebar-open');
            } else {
                const collapsed = shell.classList.toggle('sidebar-collapsed');
                try { localStorage.setItem('surya-sidebar-collapsed', String(collapsed)); }
                catch (_) {}
            }
            sync();
            if (drawerMode() && shell.classList.contains('sidebar-open')) close?.focus();
            notifyLayout();
        });
        close?.addEventListener('click', () => closeMobile(true));
        overlay.addEventListener('click', () => closeMobile(true));
        sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
            if (drawerMode()) closeMobile();
        }));
        document.addEventListener('keydown', event => {
            if (!drawerMode() || !shell.classList.contains('sidebar-open')) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopImmediatePropagation();
                closeMobile(true);
                return;
            }
            if (event.key === 'Tab') {
                const items = [...sidebar.querySelectorAll('a[href], button:not([disabled])')]
                    .filter(item => item.getClientRects().length);
                const first = items[0], last = items[items.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault(); last?.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault(); first?.focus();
                }
            }
        }, true);
        mobile.addEventListener('change', applyViewport);
        // Fullscreen uses the same drawer controller without changing desktop preferences.
        window.SuryaSidebar = {
            setMapFullscreen(active) {
                mapFullscreen = Boolean(active);
                applyViewport();
            }
        };
        // Keep externally triggered layout changes accessible too.
        // Keep focus/ARIA state correct for those layout changes too.
        new MutationObserver(sync).observe(shell, {attributes: true, attributeFilter: ['class']});
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, {once: true});
    else start();
})();
