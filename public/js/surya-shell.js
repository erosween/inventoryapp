(() => {
    'use strict';

    const start = () => {
        const shell = document.querySelector('[data-surya-workspace]');
        const sidebar = document.getElementById('surya-sidebar');
        const toggle = document.querySelector('[data-surya-sidebar-toggle]');
        const navbar = toggle?.closest('.surya-navbar');
        if (!shell || !sidebar || !toggle || shell.dataset.sidebarReady) return;
        shell.dataset.sidebarReady = 'true';
        sidebar.querySelectorAll('.sidebar-link.active').forEach(link => link.setAttribute('aria-current', 'page'));

        // Navigation feedback never blocks clicks or adds a delay to navigation.
        const progress = document.createElement('div');
        progress.className = 'surya-navigation-progress';
        progress.hidden = true;
        progress.setAttribute('role', 'status');
        progress.setAttribute('aria-live', 'polite');
        progress.innerHTML = `
            <span class="surya-navigation-icon" aria-hidden="true"></span>
            <span class="surya-navigation-copy">
                <span class="surya-navigation-label">Memuat menu</span>
                <strong class="surya-navigation-destination"></strong>
            </span>`;
        const destinationLabel = progress.querySelector('.surya-navigation-destination');
        navbar?.querySelector('.surya-navbar-copy')?.append(progress);
        let progressDelay, progressTimeout;
        const resetProgress = () => {
            clearTimeout(progressDelay);
            clearTimeout(progressTimeout);
            progress.hidden = true;
            navbar?.classList.remove('is-navigating');
        };
        document.addEventListener('click', event => {
            const link = event.target.closest?.('a.sidebar-link');
            if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
                || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
            const destination = new URL(link.href, window.location.href);
            if (destination.origin !== window.location.origin || !['http:', 'https:'].includes(destination.protocol)) return;
            if (destination.pathname === location.pathname && destination.search === location.search && destination.hash) return;
            const menuName = link.querySelector('span')?.textContent.trim() || 'SURYA';
            // Observe cancellation by existing in-page tab handlers before showing feedback.
            window.setTimeout(() => {
                if (event.defaultPrevented) return;
                resetProgress();
                progressDelay = window.setTimeout(() => {
                    destinationLabel.textContent = menuName;
                    progress.hidden = false;
                    navbar?.classList.add('is-navigating');
                }, 150);
                progressTimeout = window.setTimeout(resetProgress, 15000);
            }, 0);
        });
        window.addEventListener('pageshow', resetProgress);
        window.addEventListener('pagehide', resetProgress);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) resetProgress(); });

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
