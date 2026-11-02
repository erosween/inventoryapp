(() => {
    'use strict';
    const dialog = document.getElementById('visit-export-dialog');
    const open = document.querySelector('[data-visit-export-open]');
    if (dialog && open) {
        open.addEventListener('click', () => dialog.showModal());
        dialog.querySelector('[data-visit-export-close]').addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
        dialog.addEventListener('close', () => open.focus({preventScroll: true}));
        const form = dialog.querySelector('form');
        const from = form.elements.date_from;
        const to = form.elements.date_to;
        const validateRange = () => {
            to.setCustomValidity(from.value && to.value && to.value < from.value
                ? 'Tanggal akhir harus sama atau setelah tanggal awal.' : '');
        };
        from.addEventListener('input', validateRange);
        to.addEventListener('input', validateRange);
        form.addEventListener('submit', event => {
            validateRange();
            if (!form.reportValidity()) event.preventDefault();
        });
    }
    const tabs = [...document.querySelectorAll('[data-visit-subtab]')];
    const syncTabs = () => tabs.forEach(tab => {
        const active = tab.classList.contains('active');
        tab.setAttribute('aria-selected', String(active));
        tab.tabIndex = active ? 0 : -1;
    });
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', syncTabs);
        tab.addEventListener('keydown', event => {
            const next = event.key === 'ArrowRight' ? (index + 1) % tabs.length
                : event.key === 'ArrowLeft' ? (index + tabs.length - 1) % tabs.length
                : event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : null;
            if (next === null) return;
            event.preventDefault();
            tabs[next].click();
            tabs[next].focus();
        });
    });
    syncTabs();
})();
