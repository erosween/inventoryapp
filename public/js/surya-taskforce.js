(() => {
    'use strict';
    const toolbar = document.getElementById('taskforce-toolbar');
    if (toolbar) {
        const changeTap = () => { toolbar.elements.sf.value = ''; toolbar.requestSubmit(); };
        if (window.jQuery && jQuery.fn.select2) {
            jQuery('.tf-select').select2({width:'100%', allowClear:true, language:{noResults:()=> 'Tidak ditemukan'}});
            jQuery(toolbar.elements.tap).on('change', changeTap);
        } else toolbar.elements.tap.addEventListener('change', changeTap);
        toolbar.addEventListener('submit', () => {
            const button = toolbar.querySelector('button[type="submit"]');
            button.disabled = true; button.textContent = 'Memuat…';
        });
        window.addEventListener('pageshow', () => {
            const button = toolbar.querySelector('button[type="submit"]');
            button.disabled = false; button.textContent = 'Tampilkan';
        });
    }
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
    function openAction(button, visited = button.dataset.visited) {
        actionUrl = button.dataset.actionUrl;
        form.reset(); message.textContent = ''; save.disabled = false;
        save.textContent = 'Simpan';
        document.getElementById('action-outlet').textContent = button.dataset.outlet;
        form.elements.visited.value = visited;
        form.elements.visited_at.value = button.dataset.date || '';
        form.elements.notes.value = button.dataset.notes || '';
        updateDate(); dialog.showModal();
    }
    document.querySelectorAll('button[data-action-url]').forEach(button => button.addEventListener('click', () => openAction(button)));
    document.querySelectorAll('.tf-visit-select').forEach(select => select.addEventListener('change', () => {
        const chosen = select.value;
        select.value = select.dataset.visited;
        openAction(select, chosen);
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
