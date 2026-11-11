{{--
    Tampilan pratinjau foto SURYA yang dipakai halaman pemanggil.
--}}    <dialog id="surya-visit-photo-preview" class="surya-visit-photo-preview" aria-labelledby="visit-photo-caption">
        <header><strong id="visit-photo-caption" data-photo-caption>Foto Visit</strong>
            <button type="button" data-photo-out aria-label="Perkecil foto">−</button>
            <button type="button" data-photo-reset aria-label="Reset ukuran foto">Pas layar</button>
            <button type="button" data-photo-in aria-label="Perbesar foto">+</button>
            <button type="button" data-photo-close aria-label="Tutup preview foto" autofocus>×</button>
        </header>
        <div data-photo-stage><img alt="Foto Visit"><p data-photo-error hidden>Foto tidak dapat dimuat. Tutup lalu coba lagi.</p></div>
    </dialog>
    <script src="{{ asset('js/surya-visit-photo-preview.js') }}?v={{ filemtime(public_path('js/surya-visit-photo-preview.js')) }}" defer></script>
