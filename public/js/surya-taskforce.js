(() => {
    'use strict';
    const filters = document.getElementById('push-so-filters');
    if (!filters) return;
    if (window.jQuery && jQuery.fn.select2) {
        jQuery('.tf-select').select2({width: '100%', allowClear: true, language: {noResults: () => 'Tidak ditemukan'}});
        jQuery(filters.elements.tap).on('change', () => { filters.elements.sf.value = ''; filters.requestSubmit(); });
    } else filters.elements.tap.addEventListener('change', () => { filters.elements.sf.value = ''; filters.requestSubmit(); });
    filters.addEventListener('submit', () => {
        const button = filters.querySelector('button[type="submit"]');
        button.disabled = true;
        button.innerHTML = '<span class="tf-spinner" aria-hidden="true"></span> Memuat…';
        filters.setAttribute('aria-busy', 'true');
    });
    window.addEventListener('pageshow', () => {
        const button = filters.querySelector('button[type="submit"]');
        button.disabled = false; button.textContent = 'Tampilkan'; filters.removeAttribute('aria-busy');
    });
    const dialog = document.getElementById('push-so-dialog');
    if (!dialog) return;
    const form = document.getElementById('push-so-action');
    const save = form.querySelector('button[type="submit"]');
    const message = form.querySelector('.tf-save-message');
    let actionUrl = '';
    function updateDate() {
        const visited = form.elements.visited.value === '1';
        form.elements.visited_at.required = visited;
        document.getElementById('visit-date-label').hidden = !visited;
    }
    form.elements.visited.addEventListener('change', updateDate);
    document.querySelectorAll('[data-action-url]').forEach(button => button.addEventListener('click', () => {
        actionUrl = button.dataset.actionUrl;
        form.reset(); message.textContent = ''; save.disabled = false;
        save.textContent = 'Simpan';
        document.getElementById('action-outlet').textContent = button.dataset.outlet;
        form.elements.visited.value = button.dataset.visited;
        form.elements.visited_at.value = button.dataset.date || '';
        form.elements.notes.value = button.dataset.notes || '';
        updateDate(); dialog.showModal();
    }));
    dialog.querySelectorAll('[data-close]').forEach(button => button.addEventListener('click', () => { if (!save.disabled) dialog.close(); }));
    dialog.addEventListener('cancel', event => { if (save.disabled) event.preventDefault(); });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (save.disabled) return;
        save.disabled = true; save.innerHTML = '<span class="tf-spinner" aria-hidden="true"></span> Menyimpan…'; message.textContent = '';
        try {
            const response = await fetch(actionUrl, {method:'POST', headers:{Accept:'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content}, body:new FormData(form)});
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'Tindak lanjut gagal disimpan.');
            window.location.reload();
        } catch (error) {
            message.textContent = error.message || 'Koneksi terputus. Coba kembali.';
            save.disabled = false; save.textContent = 'Simpan';
        }
    });
})();
