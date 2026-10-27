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
    document.querySelectorAll('[data-visit-row]').forEach(row => {
        const status = row.querySelector('[name="visited"]');
        const date = row.querySelector('[name="visited_at"]');
        const notes = row.querySelector('[name="notes"]');
        const save = row.querySelector('[data-save-visit]');
        if (!save) return;
        const message = row.querySelector('.tf-row-message');
        let saving = false;
        const markDirty = () => { message.textContent = 'Belum disimpan'; message.classList.remove('saved'); };
        status.addEventListener('change', () => {
            date.disabled = status.value !== '1';
            date.required = status.value === '1';
            if (status.value === '0') date.value = '';
            markDirty();
            if (!date.disabled) date.focus();
        });
        date.addEventListener('input', markDirty);
        notes.addEventListener('input', markDirty);
        save.addEventListener('click', async () => {
            if (saving) return;
            if (status.value === '1' && !date.reportValidity()) return;
            saving = true;
            const values = {sales_cutoff:row.dataset.cutoff,visited:status.value,visited_at:status.value === '1' ? date.value : null,notes:notes.value};
            save.disabled = true; status.disabled = true; date.disabled = true; notes.disabled = true;
            save.textContent = 'Menyimpan…'; message.textContent = '';
            try {
                const response = await fetch(row.dataset.actionUrl, {
                    method:'POST', headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
                    body:JSON.stringify(values)
                });
                const data = await response.json();
                if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Gagal menyimpan.');
                message.textContent = 'Tersimpan'; message.classList.add('saved');
                if (data.updated_by) {
                    let by = notes.parentElement.querySelector('.tf-subtext');
                    if (!by) { by = document.createElement('small'); by.className = 'tf-subtext'; notes.parentElement.appendChild(by); }
                    by.textContent = data.updated_by;
                }
            } catch (error) { message.textContent = error.message || 'Koneksi terputus. Coba lagi.'; message.classList.remove('saved'); }
            finally {
                saving = false; save.disabled = false; status.disabled = false; notes.disabled = false;
                date.disabled = status.value !== '1'; save.textContent = 'Simpan';
            }
        });
    });
})();
