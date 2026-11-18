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
        const submit = form.querySelector('[data-visit-export-submit]');
        const closeButton = dialog.querySelector('[data-visit-export-close]');
        const status = form.querySelector('[data-visit-export-status]');
        const defaultButton = submit.innerHTML;
        let downloading = false;
        const validateRange = () => {
            to.setCustomValidity(from.value && to.value && to.value < from.value
                ? 'Tanggal akhir harus sama atau setelah tanggal awal.' : '');
        };
        from.addEventListener('input', validateRange);
        to.addEventListener('input', validateRange);
        form.addEventListener('submit', async event => {
            event.preventDefault();
            validateRange();
            if (!form.reportValidity() || downloading) return;

            const params = new URLSearchParams(new FormData(form));
            downloading = true;
            status.textContent = 'Sedang mengunduh file Excel…';
            status.dataset.state = 'loading';
            submit.disabled = true;
            closeButton.disabled = true;
            from.disabled = true;
            to.disabled = true;
            submit.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Sedang download…';

            try {
                const response = await fetch(`${form.action}?${params}`, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' },
                });
                if (!response.ok) throw new Error(`Server gagal membuat file (HTTP ${response.status}).`);

                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('spreadsheetml') && !contentType.includes('octet-stream')) {
                    throw new Error('Server tidak mengirim file Excel. Silakan coba lagi.');
                }

                const file = await response.blob();
                const disposition = response.headers.get('content-disposition') || '';
                const encodedName = disposition.match(/filename\*=UTF-8''([^;]+)/i)?.[1];
                const plainName = disposition.match(/filename="?([^";]+)"?/i)?.[1];
                const filename = encodedName ? decodeURIComponent(encodedName) : (plainName || `surya-penjualan-${from.value}-sampai-${to.value}.xlsx`);
                const url = URL.createObjectURL(file);
                const link = document.createElement('a');
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.setTimeout(() => URL.revokeObjectURL(url), 60_000);

                status.textContent = 'Download berhasil.';
                status.dataset.state = 'success';
                window.setTimeout(() => {
                    downloading = false;
                    dialog.close();
                }, 1000);
            } catch (error) {
                status.textContent = error.message || 'Download gagal. Silakan coba lagi.';
                status.dataset.state = 'error';
                downloading = false;
                submit.disabled = false;
                closeButton.disabled = false;
                from.disabled = false;
                to.disabled = false;
                submit.innerHTML = defaultButton;
            }
        });
        dialog.addEventListener('cancel', event => { if (downloading) event.preventDefault(); });
        dialog.addEventListener('close', () => {
            downloading = false;
            submit.disabled = false;
            closeButton.disabled = false;
            from.disabled = false;
            to.disabled = false;
            submit.innerHTML = defaultButton;
            status.textContent = '';
            delete status.dataset.state;
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
