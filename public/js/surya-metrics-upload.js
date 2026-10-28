(function (root) {
    'use strict';

    async function runBatches(request, state, onProgress) {
        onProgress(state);
        while (state.status !== 'complete') {
            if (state.status === 'cancelled') throw new Error('Upload ini sudah dihentikan. Silakan pilih file lain.');
            state = await request(state.next_row);
            onProgress(state);
        }
        return state;
    }
    root.SuryaMetricsUpload = { runBatches };
    if (!root.document) return;

    const form = document.getElementById('metrics-upload-form');
    if (!form || !root.FormData || !root.fetch) return;
    const file = form.querySelector('[name="metrics_file"]');
    const submit = form.querySelector('[type="submit"]');
    const resume = form.querySelector('[data-import-resume]');
    const change = form.querySelector('[data-import-change]');
    const panel = form.querySelector('.metrics-import-progress');
    const label = form.querySelector('[data-import-label]');
    const percent = form.querySelector('[data-import-percent]');
    const bar = form.querySelector('progress');
    const status = form.querySelector('[data-import-status]');
    const error = form.querySelector('[data-import-error]');
    const token = form.querySelector('[name="_token"]').value;
    const storageKey = 'surya.metrics-import.' + form.dataset.owner;
    let job = null;
    let busy = false;
    try { job = JSON.parse(sessionStorage.getItem(storageKey)); } catch (_) { /* Storage may be disabled. */ }

    function remember(state) {
        job = state;
        try {
            if (state.status === 'complete') sessionStorage.removeItem(storageKey);
            else sessionStorage.setItem(storageKey, JSON.stringify(state));
        } catch (_) { /* Current-page retries still work without session storage. */ }
    }
    function showProgress(state) {
        remember(state);
        panel.hidden = false;
        const total = Math.max(0, state.total_rows - 1);
        const done = Math.min(total, Math.max(0, state.next_row - 2));
        const value = total ? Math.floor(done / total * 100) : 100;
        bar.value = value;
        percent.textContent = value + '%';
        label.textContent = state.status === 'complete' ? 'Upload selesai' : 'Menyimpan transaksi';
        status.textContent = done.toLocaleString('id-ID') + ' dari ' + total.toLocaleString('id-ID') +
            ' baris · ' + state.imported.toLocaleString('id-ID') + ' diproses · ' +
            state.skipped.toLocaleString('id-ID') + ' dilewati';
    }
    function showError(e) {
        error.textContent = e.message || 'Koneksi terputus. Coba lanjutkan upload.';
        error.hidden = false;
    }
    function setBusy(value) {
        busy = value;
        submit.disabled = value || Boolean(job && job.status !== 'complete');
        file.disabled = value || Boolean(job && job.status !== 'complete');
        resume.disabled = value;
        resume.hidden = value || !job || job.status === 'complete';
        change.disabled = value;
        change.hidden = resume.hidden;
    }
    async function parseResponse(response) {
        const data = await response.json().catch(() => null);
        if (!response.ok || !data) {
            const fieldError = data && data.errors && Object.values(data.errors).flat()[0];
            throw new Error(fieldError || (data && data.message) ||
                (response.status === 419 ? 'Sesi berakhir. Login kembali, lalu lanjutkan upload.' :
                response.status === 409 ? 'Batch sebelumnya masih berjalan. Tunggu sebentar, lalu klik Lanjutkan Upload.' :
                'Server belum merespons. Klik Lanjutkan Upload untuk memeriksa progres terakhir.'));
        }
        return data;
    }
    async function process(nextRow, cancel = false) {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 45000);
        try {
            return await parseResponse(await fetch(form.dataset.processUrl.replace('__UPLOAD__', job.id), {
                method: 'POST', credentials: 'same-origin', signal: controller.signal,
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify(cancel ? { cancel: true } : nextRow === null ? {} : { next_row: nextRow })
            }));
        } catch (e) {
            if (e.name === 'AbortError' || e instanceof TypeError) {
                throw new Error('Koneksi terputus atau server belum merespons. Klik Lanjutkan Upload untuk memeriksa progres terakhir.');
            }
            throw e;
        } finally { clearTimeout(timeout); }
    }
    function upload(data) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action + '?staged=1');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.timeout = 45000;
            xhr.upload.onprogress = (event) => {
                if (!event.lengthComputable) return;
                const value = Math.floor(event.loaded / event.total * 100);
                bar.value = value;
                percent.textContent = value + '%';
                label.textContent = value === 100 ? 'Memeriksa file' : 'Mengirim file';
                status.textContent = value === 100 ? 'File terkirim. Menyiapkan batch pertama…' : 'Mengirim file ke server…';
            };
            xhr.onload = () => parseResponse({
                ok: xhr.status >= 200 && xhr.status < 300, status: xhr.status,
                json: async () => JSON.parse(xhr.responseText)
            }).then(resolve, reject);
            xhr.onerror = () => reject(new Error('File belum berhasil terkirim. Periksa koneksi dan coba upload kembali.'));
            xhr.ontimeout = () => reject(new Error('Pengiriman atau pemeriksaan file melewati batas waktu. Coba upload kembali.'));
            xhr.send(data);
        });
    }
    async function continueJob(refresh) {
        error.hidden = true;
        setBusy(true);
        try {
            if (refresh) showProgress(await process(null));
            await runBatches(process, job, showProgress);
        } catch (e) { showError(e); }
        finally { setBusy(false); }
    }
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (busy || !file.files.length) return;
        if (!/\.csv$/i.test(file.files[0].name)) { showError(new Error('Gunakan CSV UTF-8 (.csv). Di Excel pilih Save As → CSV UTF-8.')); return; }
        if (!/(cvm|biometrik)/i.test(file.files[0].name)) { showError(new Error('Nama file wajib mengandung cvm atau biometrik.')); return; }
        const data = new FormData(form);
        error.hidden = true;
        panel.hidden = false;
        label.textContent = 'Mengirim file';
        bar.value = 0;
        percent.textContent = '0%';
        setBusy(true);
        try {
            showProgress(await upload(data));
            await runBatches(process, job, showProgress);
        } catch (e) { showError(e); }
        finally { setBusy(false); }
    });
    resume.addEventListener('click', () => { if (!busy && job) continueJob(true); });
    change.addEventListener('click', async () => {
        if (busy || !job) return;
        setBusy(true);
        try {
            await process(null, true);
            try { sessionStorage.removeItem(storageKey); } catch (_) { /* Storage may be disabled. */ }
            job = null;
            file.value = '';
            panel.hidden = true;
            error.hidden = true;
        } catch (e) { showError(e); }
        finally { setBusy(false); }
    });
    root.addEventListener('beforeunload', (event) => {
        if (busy) { event.preventDefault(); event.returnValue = ''; }
    });
    if (job && /^[a-f0-9-]{36}$/i.test(job.id)) {
        showProgress(job);
        status.textContent += ' · Upload belum selesai. Klik Lanjutkan Upload.';
        setBusy(false);
    } else { job = null; }
})(typeof window === 'undefined' ? globalThis : window);

