(() => {
    'use strict';
    let cleanup = () => {};
    function init() {
    cleanup();
    const events = new AbortController();
    const menu = document.getElementById('tf-column-menu');
    if (!menu) return;
    // The popup belongs to the viewport, not the report's scrolling/layout container.
    document.body.append(menu);
    let includes = JSON.parse(menu.dataset.selection || '{}');
    let excludes = JSON.parse(menu.dataset.excluded || '{}');
    const list = menu.querySelector('[data-filter-values]');
    const search = menu.querySelector('[data-filter-search]');
    const message = menu.querySelector('[data-filter-message]');
    const apply = menu.querySelector('[data-filter-apply]');
    const more = menu.querySelector('[data-filter-more]');
    let trigger, key, mode, chosen, values, page, timer, pending;
    function navigatePage(url) {
        if (menu.dataset.preserveOutletPosition === 'true' && trigger?.closest('#sold-outlet-results')) {
            const target = new URL(url, location.href);
            target.hash = '';
            const wrap = document.querySelector('#sold-outlet-results > .pt-table-wrap');
            sessionStorage.setItem('surya-sold-outlet-scroll', JSON.stringify({
                href: target.pathname + target.search,
                x: scrollX,
                y: scrollY,
                left: wrap?.scrollLeft || 0,
                top: wrap?.scrollTop || 0,
            }));
            window.location.assign(target.toString());
            return;
        }
        if (menu.dataset.preserveScroll === 'true') window.StockOutletNavigate?.(url);
        else if (menu.dataset.standalone === 'true') window.location.assign(url);
        else if (window.SuryaTaskforceNavigate) window.SuryaTaskforceNavigate(url);
        else window.location.assign(url);
    }
    function sort(column, direction) {
        const url = new URL(location.href);
        if (trigger?.dataset.manualTable) {
            url.searchParams.set('manual_table', '1'); url.hash = 'manual-history';
            if (direction) { url.searchParams.set('manual_sort', column); url.searchParams.set('manual_direction', direction); }
            else { url.searchParams.delete('manual_sort'); url.searchParams.delete('manual_direction'); }
        } else if (trigger?.dataset.stockSummary) {
            url.searchParams.set('view', 'tap'); url.searchParams.set('stock_summary', '1'); url.hash = 'stock-outlet-tap-summary';
            if (direction) { url.searchParams.set('tap_sort', column); url.searchParams.set('tap_direction', direction); }
            else { url.searchParams.delete('tap_sort'); url.searchParams.delete('tap_direction'); }
        } else if (trigger?.dataset.summaryTable) {
            url.searchParams.set('summary_table',trigger.dataset.summaryTable);
            url.hash = `summary-${trigger.dataset.summaryTable}`;
            if (direction) { url.searchParams.set('summary_sort',column); url.searchParams.set('summary_direction',direction); }
            else { url.searchParams.delete('summary_sort'); url.searchParams.delete('summary_direction'); }
        } else if (direction) { url.searchParams.set('sort',column); url.searchParams.set('direction',direction); }
        else { url.searchParams.delete('sort'); url.searchParams.delete('direction'); }
        if (!trigger?.dataset.summaryTable && !trigger?.dataset.stockSummary && !trigger?.dataset.manualTable) url.hash = 'sold-outlet-results';
        url.searchParams.delete('page');
        navigatePage(url.toString());
    }
    menu.querySelectorAll('[data-sort-direction], [data-sort-clear]').forEach(button => {
        if (!button.dataset.idleHtml) button.dataset.idleHtml = button.innerHTML;
        button.addEventListener('click', () => {
            button.disabled = true;
            button.innerHTML = '<span class="pt-spinner" aria-hidden="true"></span> Memuat…';
            sort(key,button.dataset.sortDirection || null);
        });
    });
    let resizeObserver;
    cleanup = () => { events.abort(); pending?.abort(); clearTimeout(timer); resizeObserver?.disconnect(); };
    function close() {
        if (menu.contains(document.activeElement)) document.activeElement.blur();
        clearTimeout(timer); pending?.abort(); menu.hidden = true;
        trigger?.setAttribute('aria-expanded','false');
    }
    function positionMenu(anchor = false) {
        if (menu.hidden || !trigger?.isConnected) return;
        const rect = trigger.getBoundingClientRect();
        const column = (trigger.closest('th') || trigger).getBoundingClientRect();
        const viewport = window.visualViewport;
        const width = Math.min(viewport?.width || innerWidth, innerWidth), height = Math.min(viewport?.height || innerHeight, innerHeight);
        const left = viewport?.offsetLeft || 0, top = viewport?.offsetTop || 0;
        const touch = navigator.maxTouchPoints > 0 || window.matchMedia('(pointer:coarse)').matches;
        const menuWidth = Math.min(touch ? 360 : 310, Math.max(0,width - 16));
        menu.style.width = menuWidth + 'px';
        menu.style.maxWidth = Math.max(0,width - 16) + 'px';
        menu.style.maxHeight = Math.max(0,height - 16) + 'px';
        const desiredLeft = anchor ? column.left : (parseFloat(menu.style.left) || column.left);
        menu.style.left = (touch ? left + (width - menuWidth) / 2 : Math.max(left + 8,Math.min(desiredLeft,left + width - menuWidth - 8))) + 'px';
        const menuHeight = menu.getBoundingClientRect().height;
        const desiredTop = anchor ? rect.bottom + 6 : (parseFloat(menu.style.top) || top + 8);
        menu.style.top = Math.max(top + 8,Math.min(desiredTop,top + height - menuHeight - 8)) + 'px';
    }
    const keepInView = () => positionMenu(false);
    function draw() {
        list.replaceChildren();
        for (const value of values) {
            const label = document.createElement('label');
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.checked = mode === 'include' ? chosen.has(value) : !chosen.has(value);
            checkbox.addEventListener('change', () => {
                const keep = mode === 'include' ? checkbox.checked : !checkbox.checked;
                if (keep) chosen.add(value); else chosen.delete(value);
                apply.disabled = mode === 'include' && chosen.size === 0;
            });
            const text = document.createElement('span');
            text.textContent = value === '' ? '(Kosong)' : value;
            label.append(checkbox,text); list.append(label);
        }
        apply.disabled = mode === 'include' && chosen.size === 0;
    }
    async function load(append = false) {
        pending?.abort();
        const controller = new AbortController(); pending = controller;
        const url = new URL(menu.dataset.valuesUrl,location.href);
        url.search = location.search;
        if (menu.dataset.standalone === 'true') url.searchParams.delete('outlet_q');
        if (menu.dataset.outletSearch !== undefined) url.searchParams.set('outlet_q',menu.dataset.outletSearch);
        if (menu.dataset.valuesUrl?.includes('/sold-serials/')) url.searchParams.delete('outlet_q');
        if (trigger?.dataset.stockSummary) { url.searchParams.set('view', 'tap'); url.searchParams.set('stock_summary', '1'); }
        if (trigger?.dataset.manualTable) url.searchParams.set('manual_table', '1');
        if (trigger?.dataset.summaryTable) url.searchParams.set('summary_table',trigger.dataset.summaryTable);
        else url.searchParams.delete('summary_table');
        url.searchParams.delete('page');
        url.searchParams.set('column',key);
        url.searchParams.set('q',search.value);
        url.searchParams.set('value_page',String(page));
        message.innerHTML = '<span class="pt-spinner" aria-hidden="true"></span> Memuat nilai…'; more.hidden = true;
        if (append) { more.disabled = true; more.innerHTML = '<span class="pt-spinner" aria-hidden="true"></span> Memuat…'; }
        if (!append) { values = []; draw(); }
        try {
            const response = await fetch(url,{signal:controller.signal,headers:{Accept:'application/json'}});
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Nilai filter gagal dimuat.');
            if (controller.signal.aborted) return;
            values = [...new Set(append ? values.concat(data.values) : data.values)];
            draw(); more.hidden = !data.has_more;
            message.textContent = values.length ? '' : 'Tidak ada nilai yang sesuai.';
            positionMenu();
        } catch (error) {
            if (error.name !== 'AbortError') message.textContent = error.message || 'Koneksi terputus.';
            positionMenu();
        } finally {
            if (append) { more.disabled = false; more.textContent = 'Muat lainnya'; }
        }
    }
    function navigate(clear = false) {
        if (!clear && chosen.size > 100) { message.textContent = 'Maksimal 100 nilai pilihan. Gunakan Pilih semua untuk mengurangi pengecualian.'; return; }
        const url = new URL(location.href);
        const stockSummary = Boolean(trigger?.dataset.stockSummary);
        const manualTable = Boolean(trigger?.dataset.manualTable);
        const summary = Boolean(trigger?.dataset.summaryTable);
        const summaryLevel = trigger?.dataset.summaryTable;
        const includePrefix = manualTable ? 'manual_columns' : (stockSummary ? 'tap_columns' : (summary ? `summary_${summaryLevel}_columns` : 'columns'));
        const excludePrefix = manualTable ? 'manual_exclude' : (stockSummary ? 'tap_exclude' : (summary ? `summary_${summaryLevel}_exclude` : 'exclude'));
        for (const name of [...url.searchParams.keys()]) {
            if (name.startsWith(`${includePrefix}[${key}]`) || name.startsWith(`${excludePrefix}[${key}]`)) url.searchParams.delete(name);
        }
        if (summary) url.searchParams.set('summary_table',trigger.dataset.summaryTable);
        if (summary) url.hash = `summary-${summaryLevel}`;
        if (stockSummary) { url.searchParams.set('view', 'tap'); url.searchParams.set('stock_summary', '1'); url.hash = 'stock-outlet-tap-summary'; }
        if (manualTable) { url.searchParams.set('manual_table', '1'); url.hash = 'manual-history'; }
        else if (!stockSummary && !summary) url.hash = 'sold-outlet-results';
        if (!clear) for (const value of chosen) url.searchParams.append(`${mode === 'include' ? includePrefix : excludePrefix}[${key}][]`,value);
        url.searchParams.delete('page');
        apply.disabled = true; apply.innerHTML = '<span class="pt-spinner" aria-hidden="true"></span> Memuat…';
        navigatePage(url.toString());
    }
    document.addEventListener('taskforce:navigation-end', () => {
        apply.textContent = 'Terapkan';
        apply.disabled = mode === 'include' && chosen?.size === 0;
        menu.querySelectorAll('[data-sort-direction], [data-sort-clear]').forEach(button => {
            button.disabled = false;
            button.innerHTML = button.dataset.idleHtml || button.innerHTML;
        });
    }, {signal:events.signal});
    if (menu.dataset.preserveScroll === 'true' && !window.StockOutletNavigate) {
        let navigation;
        window.StockOutletNavigate = async (target, push = true) => {
            navigation?.abort();
            const controller = new AbortController(); navigation = controller;
            const wrap = document.querySelector('.pt-stock-outlet-scroll');
            const pagination = document.querySelector('.pt-pagination');
            const state = { x: scrollX, y: scrollY, left: wrap?.scrollLeft || 0, top: wrap?.scrollTop || 0 };
            wrap?.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch(target, {signal: controller.signal, headers: {'X-Requested-With':'XMLHttpRequest'}});
                if (!response.ok) throw Error('Data gagal dimuat. Coba kembali.');
                const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
                const nextWrap = doc.querySelector('.pt-stock-outlet-scroll');
                const nextPagination = doc.querySelector('.pt-pagination');
                const nextMenu = doc.querySelector('#tf-column-menu');
                if (!nextWrap || !nextPagination || !nextMenu) throw Error('Sesi berakhir. Muat ulang halaman untuk masuk kembali.');
                if (controller.signal.aborted) return;
                wrap.innerHTML = nextWrap.innerHTML;
                pagination.innerHTML = nextPagination.innerHTML;
                menu.dataset.selection = nextMenu.dataset.selection || '{}';
                menu.dataset.excluded = nextMenu.dataset.excluded || '{}';
                menu.dataset.valuesUrl = nextMenu.dataset.valuesUrl || menu.dataset.valuesUrl;
                menu.hidden = true;
                if (push) history.pushState({}, '', target);
                const restore = () => { wrap.scrollLeft = state.left; wrap.scrollTop = state.top; window.scrollTo(state.x, state.y); };
                restore(); requestAnimationFrame(restore);
                document.dispatchEvent(new Event('taskforce:updated'));
            } catch (error) {
                if (error.name !== 'AbortError') {
                    menu.hidden = false;
                    menu.querySelector('[data-filter-message]').textContent = error.message;
                }
            } finally {
                if (navigation === controller) {
                    wrap?.removeAttribute('aria-busy');
                    document.dispatchEvent(new Event('taskforce:navigation-end'));
                }
            }
        };
        document.addEventListener('click', event => {
            const link = event.target.closest('.pt-pagination a[href]');
            if (!link || link.getAttribute('aria-disabled') === 'true') return;
            event.preventDefault(); window.StockOutletNavigate(link.href);
        }, {signal:events.signal});
        window.addEventListener('popstate', () => window.StockOutletNavigate(location.href, false), {signal:events.signal});
    }
    document.querySelectorAll('.tf-column-filter').forEach(button => button.addEventListener('click', async () => {
        if (!menu.hidden && trigger === button) { close(); return; }
        close(); trigger = button; key = button.dataset.column;
        includes = JSON.parse(button.dataset.selection || menu.dataset.selection || '{}');
        excludes = JSON.parse(button.dataset.excluded || menu.dataset.excluded || '{}');
        mode = Object.prototype.hasOwnProperty.call(includes,key) ? 'include' : 'exclude';
        chosen = new Set((mode === 'include' ? includes[key] : excludes[key]) || []);
        values = []; page = 1; search.value = ''; apply.textContent = 'Terapkan';
        document.getElementById('tf-column-title').textContent = 'Filter · ' + button.dataset.label;
        menu.hidden = false; button.setAttribute('aria-expanded','true');
        positionMenu(true);
        // On touch screens the keyboard should open only when search is tapped.
        if (!(navigator.maxTouchPoints > 0 || window.matchMedia('(pointer:coarse)').matches)) search.focus({preventScroll:true});
        // Keep the trigger clickable while facets load. Disabling it here made
        // the menu feel frozen and prevented users from closing/reopening it
        // when a large report took longer to answer.
        button.setAttribute('aria-busy','true');
        try { await load(); }
        finally { if (button.isConnected) button.removeAttribute('aria-busy'); }
    }));
    search.addEventListener('input', () => { clearTimeout(timer); pending?.abort(); timer = setTimeout(() => { page = 1; load(); },250); });
    more.addEventListener('click', () => { page++; load(true); });
    menu.querySelector('[data-filter-all]').addEventListener('click', () => { mode = 'exclude'; chosen.clear(); draw(); });
    menu.querySelector('[data-filter-none]').addEventListener('click', () => { mode = 'include'; chosen.clear(); draw(); });
    menu.querySelector('[data-filter-reset]').addEventListener('click', event => { const button = event.currentTarget; button.disabled = true; button.innerHTML = '<span class="pt-spinner" aria-hidden="true"></span> Memuat…'; navigate(true); });
    apply.addEventListener('click', () => navigate());
    menu.querySelector('[data-filter-close]').addEventListener('click', () => { close(); trigger?.focus(); });
    document.addEventListener('click', event => { if (!menu.hidden && !menu.contains(event.target) && !trigger?.contains(event.target)) close(); },{signal:events.signal});
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && !menu.hidden) { close(); trigger?.focus(); } },{signal:events.signal});
    document.querySelector('.tf-table-card .table-wrap')?.addEventListener('scroll',keepInView,{passive:true,signal:events.signal});
    window.addEventListener('scroll',keepInView,{passive:true,signal:events.signal});
    document.querySelector('.detail-table')?.closest('.table-wrap')?.addEventListener('scroll',keepInView,{passive:true,signal:events.signal});
    window.addEventListener('resize',keepInView,{signal:events.signal});
    window.addEventListener('surya:layout',keepInView,{signal:events.signal});
    window.visualViewport?.addEventListener('resize',keepInView,{signal:events.signal});
    window.visualViewport?.addEventListener('scroll',keepInView,{passive:true,signal:events.signal});
    if (typeof ResizeObserver !== 'undefined') {
        resizeObserver = new ResizeObserver(keepInView);
        resizeObserver.observe(menu);
    }
    }
    init();
    document.addEventListener('taskforce:updated',init);
})();
