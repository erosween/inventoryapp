(function (root) {
    'use strict';

    const tapOf = outlet => String(outlet.tap || 'Tanpa TAP');
    const qualityOf = outlet => outlet.branding_dominance === 'No Branding'
        ? 'not_applicable' : String(outlet.branding_quality || '').trim() || 'missing';

    function selectOutlets(outlets, filters = {}) {
        const terms = String(filters.search || '').trim().toLocaleLowerCase('id-ID').split(/\s+/).filter(Boolean);
        return outlets.filter(outlet => {
            if (filters.tap && tapOf(outlet) !== filters.tap) return false;
            if (filters.visit === 'visited' && !outlet.has_front_photo) return false;
            if (filters.visit === 'unvisited' && outlet.has_front_photo) return false;
            if (filters.brand && (outlet.branding_dominance || 'missing') !== filters.brand) return false;
            if (filters.quality && qualityOf(outlet) !== filters.quality) return false;
            const haystack = [outlet.id_outlet, outlet.nama_outlet, outlet.sf, tapOf(outlet)].join(' ').toLocaleLowerCase('id-ID');
            return terms.every(term => haystack.includes(term));
        }).sort((a, b) => String(a.nama_outlet || '').localeCompare(String(b.nama_outlet || ''), 'id-ID')
            || String(a.id_outlet).localeCompare(String(b.id_outlet)));
    }
    root.SuryaBrandingInsights = { selectOutlets, qualityOf };
    if (!root.document) return;

    const dialog = document.getElementById('branding-outlet-dialog');
    const insights = document.getElementById('branding-insights');
    if (!dialog || !insights) return;

    const fields = Object.fromEntries(['tap', 'visit', 'brand', 'quality', 'search'].map(name => [name, dialog.querySelector('[data-insight-filter="' + name + '"]')]));
    const body = dialog.querySelector('tbody');
    const count = dialog.querySelector('[data-insight-count]');
    const title = dialog.querySelector('[data-insight-title]');
    const description = dialog.querySelector('[data-insight-description]');
    const summary = dialog.querySelector('[data-insight-summary]');
    const pageLabel = dialog.querySelector('[data-insight-page]');
    const previous = dialog.querySelector('[data-insight-previous]');
    const next = dialog.querySelector('[data-insight-next]');
    const size = dialog.querySelector('[data-insight-size]');
    const empty = dialog.querySelector('[data-insight-empty]');
    const number = value => value.toLocaleString('id-ID');
    let outlets = [], filtered = [], page = 1, onMapSelect = null;
    let pageLock = null;

    function lockPage() {
        const style = document.body.style;
        pageLock = {scroll: root.scrollY, styles: Object.fromEntries(['position', 'top', 'left', 'right', 'width'].map(key => [key, style[key]]))};
        // Freeze the current background position on mobile WebKit as well as
        // desktop browsers; only the list inside the dialog should scroll.
        style.position = 'fixed';
        style.top = -pageLock.scroll + 'px';
        style.left = '0';
        style.right = '0';
        style.width = '100%';
        document.documentElement.classList.add('branding-outlet-dialog-open');
        document.body.classList.add('branding-outlet-dialog-open');
    }

    function unlockPage() {
        if (!pageLock) return;
        const saved = pageLock;
        pageLock = null;
        Object.assign(document.body.style, saved.styles);
        document.documentElement.classList.remove('branding-outlet-dialog-open');
        document.body.classList.remove('branding-outlet-dialog-open');
        root.scrollTo(0, saved.scroll);
    }

    function cell(row, label, value, className) {
        const td = document.createElement('td');
        td.dataset.label = label;
        if (className) td.className = className;
        td.textContent = value;
        row.appendChild(td);
        return td;
    }

    function render() {
        const filters = Object.fromEntries(Object.entries(fields).map(([name, field]) => [name, field.value]));
        filtered = selectOutlets(outlets, filters);
        const perPage = Number(size.value);
        const pages = Math.max(1, Math.ceil(filtered.length / perPage));
        page = Math.min(page, pages);
        const start = (page - 1) * perPage;
        title.textContent = filters.tap ? 'Outlet ' + filters.tap : 'Daftar Outlet';
        const selected = [
            fields.visit.selectedOptions[0].textContent,
            fields.brand.selectedOptions[0].textContent,
            fields.quality.selectedOptions[0].textContent
        ];
        description.textContent = selected.join(' · ');
        count.textContent = number(filtered.length) + ' outlet';
        summary.textContent = filtered.length
            ? 'Menampilkan ' + number(start + 1) + '–' + number(Math.min(start + perPage, filtered.length)) + ' dari ' + number(filtered.length) + ' outlet'
            : '0 outlet sesuai filter';
        pageLabel.textContent = number(page) + ' / ' + number(pages);
        previous.disabled = page <= 1;
        next.disabled = page >= pages;
        empty.hidden = filtered.length > 0;
        const fragment = document.createDocumentFragment();
        filtered.slice(start, start + perPage).forEach(outlet => {
            const tr = document.createElement('tr');
            const identity = cell(tr, 'Outlet', '', 'insight-outlet-identity');
            const name = document.createElement('strong');
            name.textContent = outlet.nama_outlet || 'Tanpa nama';
            const id = document.createElement('small');
            id.textContent = String(outlet.id_outlet);
            identity.append(name, id);
            cell(tr, 'Sales Force', outlet.sf || 'UNMAPPING');
            cell(tr, 'TAP', tapOf(outlet));
            const visit = cell(tr, 'Visit', '');
            const badge = document.createElement('span');
            badge.className = 'insight-visit-badge ' + (outlet.has_front_photo ? 'visited' : 'unvisited');
            badge.textContent = outlet.has_front_photo ? 'Sudah Visit' : 'Belum Visit';
            visit.appendChild(badge);
            if (outlet.has_front_photo) {
                const approval = document.createElement('small');
                approval.className = 'insight-approval';
                const labels = {verified:'Disetujui',rejected:'Foto ditolak',pending:'Menunggu persetujuan',unverified:'Perlu diperiksa'};
                approval.textContent = labels[outlet.map_visit_state] || 'Perlu diperiksa';
                visit.appendChild(approval);
            }
            cell(tr, 'Dominan Branding', outlet.branding_dominance || 'Belum diisi');
            const quality = qualityOf(outlet);
            const qualityLabel = quality === 'not_applicable' ? 'Tidak berlaku' : quality === 'missing' ? 'Belum diisi' : quality;
            const qualityCell = cell(tr, 'Kelayakan', '');
            const qualityBadge = document.createElement('span');
            qualityBadge.className = 'insight-quality ' + ({Bagus:'good','Kurang Bagus':'fair','Tidak Bagus':'poor'}[quality] || 'unknown');
            qualityBadge.textContent = qualityLabel;
            qualityCell.appendChild(qualityBadge);
            const action = cell(tr, 'Lokasi', '', 'insight-map-cell');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'insight-map-link';
            button.dataset.insightMapId = String(outlet.id_outlet);
            button.textContent = 'Lihat di Peta';
            button.setAttribute('aria-label', 'Lihat ' + (outlet.nama_outlet || outlet.id_outlet) + ' di peta');
            action.appendChild(button);
            fragment.appendChild(tr);
        });
        body.replaceChildren(fragment);
        dialog.querySelector('.insight-outlet-table-wrap').scrollTop = 0;
    }

    root.SuryaBrandingInsights.setOutlets = function (rows, selectOnMap) {
        outlets = rows;
        onMapSelect = selectOnMap;
        const currentTap = fields.tap.value;
        fields.tap.replaceChildren(new Option('Semua TAP', ''));
        [...new Set(rows.map(tapOf))].sort().forEach(tap => fields.tap.add(new Option(tap, tap)));
        fields.tap.value = currentTap;
        if (dialog.open) render();
    };

    insights.addEventListener('click', event => {
        const trigger = event.target.closest('[data-insight-open]');
        if (!trigger) return;
        fields.tap.value = trigger.dataset.tap || '';
        fields.visit.value = trigger.dataset.visit || '';
        fields.brand.value = trigger.dataset.brand || '';
        fields.quality.value = '';
        fields.search.value = '';
        page = 1;
        render();
        lockPage();
        dialog.showModal();
        const initialFocus = root.matchMedia('(max-width: 680px)').matches
            ? dialog.querySelector('[data-insight-close]') : fields.search;
        initialFocus.focus({preventScroll: true});
    });
    Object.entries(fields).forEach(([name, field]) => field.addEventListener(name === 'search' ? 'input' : 'change', () => { page = 1; render(); }));
    size.addEventListener('change', () => { page = 1; render(); });
    previous.addEventListener('click', () => { page--; render(); });
    next.addEventListener('click', () => { page++; render(); });
    dialog.querySelector('[data-insight-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', unlockPage);
    dialog.addEventListener('click', event => {
        const button = event.target.closest('[data-insight-map-id]');
        if (!button) return;
        const outlet = filtered.find(item => String(item.id_outlet) === button.dataset.insightMapId);
        if (!outlet) return;
        dialog.close();
        unlockPage();
        onMapSelect?.(outlet);
    });
})(typeof window === 'undefined' ? globalThis : window);

