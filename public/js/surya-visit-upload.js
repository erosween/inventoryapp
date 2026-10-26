(function(root) {
    'use strict';

    // Keep the 1600px photo readable, spending fewer bytes on mobile uploads.
    async function compress(canvas) {
        let blob;
        for (const quality of [0.76, 0.64, 0.56]) {
            blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', quality));
            if (!blob) throw new Error('Foto gagal dikompresi. Silakan ambil ulang.');
            if (blob.size <= 320 * 1024) break;
        }
        return blob;
    }

    function send(form, onProgress) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', form.action);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.timeout = 120000;
            xhr.upload.onprogress = event => {
                if (event.lengthComputable) onProgress(Math.min(100, Math.round(event.loaded / event.total * 100)));
            };
            xhr.upload.onload = () => onProgress(100);
            xhr.onload = () => {
                let data;
                try { data = JSON.parse(xhr.responseText); } catch (_) { data = {}; }
                if (xhr.status >= 200 && xhr.status < 300 && data.redirect_url) {
                    resolve(data);
                    return;
                }
                const validation = Object.values(data.errors || {}).flat().join('\n');
                const message = validation || (xhr.status === 413
                    ? 'Ukuran foto melebihi batas server. Foto tetap ada di halaman ini.'
                    : xhr.status === 419
                        ? 'Sesi keamanan berakhir. Buka kembali halaman visit setelah login.'
                        : data.message || 'Server belum mengonfirmasi penyimpanan foto. Cek status visit sebelum mencoba lagi.');
                reject(Object.assign(new Error(message), {status: xhr.status, errors: data.errors, redirect: data.redirect}));
            };
            const interrupted = () => reject(new Error('Koneksi terputus atau terlalu lama. Foto tetap ada di halaman ini. Cek status visit sebelum mencoba lagi.'));
            xhr.onerror = interrupted;
            xhr.ontimeout = interrupted;
            xhr.onabort = interrupted;
            onProgress(0);
            xhr.send(new FormData(form));
        });
    }

    root.SuryaVisitUpload = {compress, send};
})(window);
