(() => {
    'use strict';

    let pending;

    async function load(card, tab) {
        const product = card.querySelector('[data-validity-product]');
        const month = card.querySelector('[data-validity-month]');
        if (!month.reportValidity()) return;

        pending?.abort();
        const controller = new AbortController();
        pending = controller;
        const wrap = card.querySelector('.validity-scroll');
        const { scrollLeft, scrollTop } = wrap;
        const status = card.querySelector('.validity-status');
        card.setAttribute('aria-busy', 'true');
        status.hidden = false;
        status.textContent = 'Memuat MoM per validity…';

        const filters = { validity_product: product.value, validity_tab: tab, validity_month: month.value };
        const url = new URL(location.href);
        for (const [key, value] of Object.entries(filters)) url.searchParams.set(key, value);

        try {
            const response = await fetch(url, {
                signal: controller.signal,
                headers: { 'X-Surya-Card': 'validity-mom' }
            });
            if (!response.ok) throw Error('Data gagal dimuat. Silakan coba kembali.');
            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
            const next = doc.getElementById('validity-mom-card');
            if (!next) throw Error('Sesi berakhir. Muat ulang halaman untuk masuk kembali.');
            if (pending !== controller) return;

            const { scrollX, scrollY } = window;
            card.replaceWith(next);
            const currentUrl = new URL(location.href);
            for (const [key, value] of Object.entries(filters)) currentUrl.searchParams.set(key, value);
            history.replaceState({}, '', currentUrl);
            const nextWrap = next.querySelector('.validity-scroll');
            nextWrap.scrollLeft = scrollLeft;
            nextWrap.scrollTop = scrollTop;
            window.scrollTo(scrollX, scrollY);
        } catch (error) {
            if (pending !== controller || error.name === 'AbortError') return;
            status.textContent = error.message;
            product.value = card.dataset.currentProduct;
            month.value = month.defaultValue;
            card.removeAttribute('aria-busy');
        }
    }

    document.addEventListener('change', event => {
        if (!event.target.matches('[data-validity-product], [data-validity-month]')) return;
        const card = event.target.closest('#validity-mom-card');
        load(card, card.dataset.currentTab);
    });

    document.addEventListener('click', event => {
        const button = event.target.closest('[data-validity-tab]');
        if (!button) return;
        load(button.closest('#validity-mom-card'), button.dataset.validityTab);
    });
})();
