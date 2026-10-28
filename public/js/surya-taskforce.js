(() => {
    'use strict';
    let navigating = null;
    window.SuryaTaskforceNavigate = async (target, push = true) => {
        navigating?.abort();
        const request = new AbortController(); navigating = request;
        const scroll = document.querySelector('.tf-table-card .table-wrap');
        const position = {x:window.scrollX,y:window.scrollY,left:scroll?.scrollLeft || 0,top:scroll?.scrollTop || 0,height:scroll?.clientHeight || 0};
        const content = document.querySelector('.taskforce-content');
        content?.setAttribute('aria-busy','true');
        try {
            const response = await fetch(target,{signal:request.signal,headers:{Accept:'text/html','X-Requested-With':'XMLHttpRequest'}});
            const html = await response.text();
            if (!response.ok) throw new Error('Data gagal dimuat. Coba kembali.');
            const doc = new DOMParser().parseFromString(html,'text/html');
            const next = doc.querySelector('.taskforce-content'), menu = doc.getElementById('tf-column-menu');
            if (!next || !menu) throw new Error('Sesi berakhir atau data tidak valid. Silakan muat ulang halaman.');
            content.innerHTML = next.innerHTML;
            document.getElementById('tf-column-menu').replaceWith(menu);
            if (push) history.pushState({},'',target);
            document.dispatchEvent(new Event('taskforce:updated'));
            const restore = () => {
                const table = document.querySelector('.tf-table-card .table-wrap');
                if (table) { table.style.minHeight = position.height + 'px'; table.scrollLeft = position.left; table.scrollTop = position.top; }
                window.scrollTo(position.x,position.y);
            };
            restore(); requestAnimationFrame(restore);
            return true;
        } catch (error) {
            if (error.name !== 'AbortError') {
                const message = document.querySelector('[data-filter-message]');
                if (message && !document.getElementById('tf-column-menu').hidden) message.textContent = error.message;
                else {
                    let alert = document.getElementById('tf-load-error');
                    if (!alert) { alert = document.createElement('p'); alert.id = 'tf-load-error'; alert.className = 'tf-alert'; alert.setAttribute('role','alert'); content.prepend(alert); }
                    alert.textContent = error.message;
                }
            }
            return false;
        } finally {
            if (navigating === request) {
                content?.removeAttribute('aria-busy');
                document.querySelectorAll('[data-filter-apply],#taskforce-toolbar button[type="submit"]').forEach(button => { button.disabled = false; button.textContent = button.matches('[data-filter-apply]') ? 'Terapkan' : 'Tampilkan'; });
            }
        }
    };
    window.addEventListener('popstate',()=>window.SuryaTaskforceNavigate(location.href,false));
    let cleanup = () => {};
    function init() {
    cleanup();
    const events = new AbortController(); cleanup = () => events.abort();
    const toolbar = document.getElementById('taskforce-toolbar');
    if (toolbar) {
        const changeTap = () => { toolbar.elements.sf.value = ''; toolbar.requestSubmit(); };
        if (window.jQuery && jQuery.fn.select2) {
            jQuery('.tf-select').select2({width:'100%', allowClear:true, language:{noResults:()=> 'Tidak ditemukan'}});
            jQuery(toolbar.elements.tap).on('change', changeTap);
        } else toolbar.elements.tap.addEventListener('change', changeTap);
        toolbar.addEventListener('submit', event => {
            event.preventDefault();
            const button = toolbar.querySelector('button[type="submit"]');
            button.disabled = true; button.textContent = 'Memuat…';
            const url = new URL(location.href); url.search = new URLSearchParams(new FormData(toolbar)).toString(); url.searchParams.delete('page');
            window.SuryaTaskforceNavigate(url.toString());
        });
        window.addEventListener('pageshow', () => {
            const button = toolbar.querySelector('button[type="submit"]');
            button.disabled = false; button.textContent = 'Tampilkan';
        },{signal:events.signal});
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
    document.querySelectorAll('.pagination a,.tf-toolbar a.btn-secondary').forEach(link=>link.addEventListener('click',event=>{
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        event.preventDefault(); window.SuryaTaskforceNavigate(link.href);
    }));
    }
    init();
    document.addEventListener('taskforce:updated',init);
})();
