(() => {
    'use strict';
    let cleanup = () => {};
    function init() {
    cleanup();
    const events = new AbortController();
    const menu = document.getElementById('tf-column-menu');
    if (!menu) return;
    const includes = JSON.parse(menu.dataset.selection || '{}');
    const excludes = JSON.parse(menu.dataset.excluded || '{}');
    const list = menu.querySelector('[data-filter-values]');
    const search = menu.querySelector('[data-filter-search]');
    const message = menu.querySelector('[data-filter-message]');
    const apply = menu.querySelector('[data-filter-apply]');
    const more = menu.querySelector('[data-filter-more]');
    let trigger, key, mode, chosen, values, page, timer, pending;
    function sort(column, direction) {
        const url = new URL(location.href);
        if (direction) { url.searchParams.set('sort',column); url.searchParams.set('direction',direction); }
        else { url.searchParams.delete('sort'); url.searchParams.delete('direction'); }
        url.searchParams.delete('page');
        window.SuryaTaskforceNavigate(url.toString());
    }
    menu.querySelectorAll('[data-sort-direction]').forEach(button => button.addEventListener('click', () => sort(key,button.dataset.sortDirection)));
    menu.querySelector('[data-sort-clear]').addEventListener('click', () => sort(key,null));
    cleanup = () => { events.abort(); pending?.abort(); clearTimeout(timer); };
    function close() {
        clearTimeout(timer); pending?.abort(); menu.hidden = true;
        trigger?.setAttribute('aria-expanded','false');
    }
    function positionMenu() {
        if (menu.hidden || !trigger?.isConnected) return;
        const rect = trigger.getBoundingClientRect();
        const column = trigger.closest('th').getBoundingClientRect();
        menu.style.left = Math.max(8,Math.min(column.left,innerWidth - 326)) + 'px';
        menu.style.top = Math.max(8,Math.min(rect.bottom + 6,innerHeight - Math.min(460,innerHeight - 16))) + 'px';
    }
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
        url.searchParams.delete('page');
        url.searchParams.set('column',key);
        url.searchParams.set('q',search.value);
        url.searchParams.set('value_page',String(page));
        message.textContent = 'Memuat nilai…'; more.hidden = true;
        if (!append) { values = []; draw(); }
        try {
            const response = await fetch(url,{signal:controller.signal,headers:{Accept:'application/json'}});
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Nilai filter gagal dimuat.');
            values = [...new Set(append ? values.concat(data.values) : data.values)];
            draw(); more.hidden = !data.has_more;
            message.textContent = values.length ? '' : 'Tidak ada nilai yang sesuai.';
        } catch (error) {
            if (error.name !== 'AbortError') message.textContent = error.message || 'Koneksi terputus.';
        }
    }
    function navigate(clear = false) {
        if (!clear && chosen.size > 100) { message.textContent = 'Maksimal 100 nilai pilihan. Gunakan Pilih semua untuk mengurangi pengecualian.'; return; }
        const url = new URL(location.href);
        for (const name of [...url.searchParams.keys()]) {
            if (name.startsWith(`columns[${key}]`) || name.startsWith(`exclude[${key}]`)) url.searchParams.delete(name);
        }
        if (!clear) for (const value of chosen) url.searchParams.append(`${mode === 'include' ? 'columns' : 'exclude'}[${key}][]`,value);
        url.searchParams.delete('page');
        apply.disabled = true; apply.textContent = 'Memuat…';
        window.SuryaTaskforceNavigate(url.toString());
    }
    document.querySelectorAll('.tf-column-filter').forEach(button => button.addEventListener('click', () => {
        if (!menu.hidden && trigger === button) { close(); return; }
        close(); trigger = button; key = button.dataset.column;
        mode = Object.prototype.hasOwnProperty.call(includes,key) ? 'include' : 'exclude';
        chosen = new Set((mode === 'include' ? includes[key] : excludes[key]) || []);
        values = []; page = 1; search.value = ''; apply.textContent = 'Terapkan';
        document.getElementById('tf-column-title').textContent = 'Filter · ' + button.dataset.label;
        menu.hidden = false; button.setAttribute('aria-expanded','true');
        positionMenu();
        search.focus({preventScroll:true}); load();
    }));
    search.addEventListener('input', () => { clearTimeout(timer); pending?.abort(); timer = setTimeout(() => { page = 1; load(); },250); });
    more.addEventListener('click', () => { page++; load(true); });
    menu.querySelector('[data-filter-all]').addEventListener('click', () => { mode = 'exclude'; chosen.clear(); draw(); });
    menu.querySelector('[data-filter-none]').addEventListener('click', () => { mode = 'include'; chosen.clear(); draw(); });
    menu.querySelector('[data-filter-reset]').addEventListener('click', () => navigate(true));
    apply.addEventListener('click', () => navigate());
    menu.querySelector('[data-filter-close]').addEventListener('click', () => { close(); trigger?.focus(); });
    document.addEventListener('click', event => { if (!menu.hidden && !menu.contains(event.target) && !trigger?.contains(event.target)) close(); },{signal:events.signal});
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && !menu.hidden) { close(); trigger?.focus(); } },{signal:events.signal});
    document.querySelector('.tf-table-card .table-wrap')?.addEventListener('scroll',positionMenu,{passive:true});
    window.addEventListener('resize',close,{signal:events.signal});
    }
    init();
    document.addEventListener('taskforce:updated',init);
})();
