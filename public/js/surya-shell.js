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
            const visible = mobile.matches
                ? shell.classList.contains('sidebar-open')
                : !shell.classList.contains('sidebar-collapsed');
            sidebar.inert = !visible;
            sidebar.setAttribute('aria-hidden', String(!visible));
            toggle.setAttribute('aria-expanded', String(visible));
            document.body.classList.toggle('surya-menu-open', mobile.matches && visible);
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
            shell.classList.remove('sidebar-open');
            shell.classList.toggle('sidebar-collapsed', !mobile.matches && readCollapsed());
            root.classList.remove('surya-sidebar-precollapsed');
            sync();
            notifyLayout();
        };

        applyViewport();
        toggle.addEventListener('click', () => {
            if (mobile.matches) {
                shell.classList.toggle('sidebar-open');
            } else {
                const collapsed = shell.classList.toggle('sidebar-collapsed');
                try { localStorage.setItem('surya-sidebar-collapsed', String(collapsed)); }
                catch (_) {}
            }
            sync();
            if (mobile.matches && shell.classList.contains('sidebar-open')) close?.focus();
            notifyLayout();
        });
        close?.addEventListener('click', () => closeMobile(true));
        overlay.addEventListener('click', () => closeMobile(true));
        sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
            if (mobile.matches) closeMobile();
        }));
        document.addEventListener('keydown', event => {
            if (!mobile.matches || !shell.classList.contains('sidebar-open')) return;
            if (event.key === 'Escape') closeMobile(true);
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
        });
        mobile.addEventListener('change', applyViewport);
        // The map may temporarily collapse navigation while entering fullscreen.
        // Keep focus/ARIA state correct for those layout changes too.
        new MutationObserver(sync).observe(shell, {attributes: true, attributeFilter: ['class']});
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, {once: true});
    else start();
})();
