(() => {
    'use strict';
    const dialog = document.getElementById('tf-visit-dialog');
    if (!dialog) return;
    const form = document.getElementById('tf-visit-form'), close = dialog.querySelector('[data-visit-close]');
    const submit = dialog.querySelector('[data-submit-visit]'), message = dialog.querySelector('[data-visit-message]');
    const historyPanel = dialog.querySelector('[data-visit-history-panel]'), items = dialog.querySelector('[data-history-items]');
    const status = dialog.querySelector('[data-history-status]'), more = dialog.querySelector('[data-history-more]');
    let target, requestId, busy = false, historyRequest, page = 0;
    const uuid = () => {
        if (crypto.randomUUID) return crypto.randomUUID();
        const bytes = crypto.getRandomValues(new Uint8Array(16)); bytes[6] = bytes[6] & 15 | 64; bytes[8] = bytes[8] & 63 | 128;
        return [...bytes].map((n, i) => ([4,6,8,10].includes(i) ? '-' : '') + n.toString(16).padStart(2, '0')).join('');
    };
    function text(tag, value, className) {
        const el = document.createElement(tag); el.textContent = value || '—'; if (className) el.className = className; return el;
    }
    function date(value) {
        const raw = String(value || '').slice(0, 10); return /^\d{4}-\d{2}-\d{2}$/.test(raw) ? raw.split('-').reverse().join('/') : raw;
    }
    async function loadHistory(append = false) {
        historyRequest?.abort(); const ctrl = new AbortController(); historyRequest = ctrl;
        if (!append) { items.replaceChildren(); page = 0; }
        more.hidden = true; status.textContent = 'Memuat riwayat…';
        const url = new URL(target.historyUrl, location.href); url.searchParams.set('sales_cutoff', target.cutoff);
        url.searchParams.set('page', String(page + 1));
        for (const name of ['visit_from', 'visit_to']) {
            const value = document.querySelector(`#taskforce-toolbar [name="${name}"]`)?.value;
            if (value) url.searchParams.set(name, value);
        }
        try {
            const response = await fetch(url, {signal: ctrl.signal, headers: {Accept: 'application/json'}});
            if (!response.ok) throw new Error('Riwayat tidak dapat dimuat. Periksa sesi dan coba lagi.');
            if (!(response.headers.get('content-type') || '').includes('application/json')) throw new Error('Sesi berakhir. Muat ulang halaman untuk login.');
            const data = await response.json();
            if (historyRequest !== ctrl) return;
            for (const visit of data.visits) {
                const card = document.createElement('article'); card.className = 'tf-history-item';
                card.append(text('strong', date(visit.visited_at)), text('small', visit.username, 'tf-history-user'));
                card.append(text('h3', 'Hasil temuan'), text('p', visit.notes));
                if (visit.follow_up) card.append(text('h3', 'Tindak lanjut'), text('p', visit.follow_up));
                card.append(text('small', `Periode taskforce ${String(visit.period_month).slice(0,7)} · Dicatat ${date(visit.created_at)} ${String(visit.created_at).slice(11,16)}`, 'tf-history-user'));
                items.append(card);
            }
            page = data.page; more.hidden = !data.has_more;
            status.textContent = data.total ? `${data.total.toLocaleString('id-ID')} kunjungan sesuai rentang tanggal` : 'Belum ada kunjungan pada rentang tanggal ini.';
        } catch (error) {
            if (error.name !== 'AbortError') { status.textContent = error.message; more.textContent = 'Coba lagi'; more.hidden = false; }
        }
    }
    function showTab(tab) {
        form.hidden = tab !== 'form'; historyPanel.hidden = tab !== 'history';
        dialog.querySelectorAll('[data-visit-tab]').forEach(button => { button.classList.toggle('active', button.dataset.visitTab === tab); button.setAttribute('aria-pressed', String(button.dataset.visitTab === tab)); });
        if (tab === 'history') { more.textContent = 'Muat lainnya'; loadHistory(); }
    }
    document.addEventListener('click', event => {
        const button = event.target.closest('[data-add-visit],[data-visit-history]'); if (!button) return;
        const row = button.closest('[data-visit-row]'); target = {...row.dataset};
        historyRequest?.abort(); form.reset(); requestId = uuid(); message.textContent = ''; message.classList.remove('saved');
        dialog.querySelector('[data-visit-outlet]').textContent = `${target.outletName} · ${target.outletId}`;
        // The date must be chosen deliberately; a historical task can be visited today.
        dialog.showModal(); showTab(button.hasAttribute('data-add-visit') ? 'form' : 'history');
    });
    dialog.querySelectorAll('[data-visit-tab]').forEach(button => button.addEventListener('click', () => { if (!busy) showTab(button.dataset.visitTab); }));
    close.addEventListener('click', () => { if (!busy) dialog.close(); });
    dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
    dialog.addEventListener('close', () => historyRequest?.abort());
    more.addEventListener('click', () => loadHistory(true));
    form.addEventListener('submit', async event => {
        event.preventDefault(); if (busy || !form.reportValidity()) return;
        busy = true; submit.disabled = true; close.disabled = true; message.textContent = 'Menyimpan…'; message.classList.remove('saved');
        const payload = {sales_cutoff: target.cutoff, request_id: requestId, visited_at: form.elements.visited_at.value, notes: form.elements.notes.value, follow_up: form.elements.follow_up.value};
        for (const input of form.querySelectorAll('input,textarea')) input.disabled = true;
        try {
            const response = await fetch(target.actionUrl, {method: 'POST', headers: {Accept: 'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify(payload)});
            if (!(response.headers.get('content-type') || '').includes('application/json')) throw new Error('Sesi berakhir. Muat ulang halaman untuk login.');
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Kunjungan gagal disimpan.');
            message.textContent = 'Kunjungan tersimpan.'; message.classList.add('saved');
            form.reset(); requestId = uuid(); showTab('history');
            await window.SuryaTaskforceNavigate(location.href, false);
        } catch (error) { message.textContent = error.message || 'Koneksi terputus. Coba simpan lagi; kunjungan tidak akan diduplikasi.'; }
        finally { busy = false; submit.disabled = false; close.disabled = false; for (const input of form.querySelectorAll('input,textarea')) input.disabled = false; }
    });
})();
