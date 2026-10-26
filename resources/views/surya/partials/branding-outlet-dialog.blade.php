<dialog id="branding-outlet-dialog" class="branding-outlet-dialog" aria-labelledby="branding-outlet-title" aria-describedby="branding-outlet-description">
    <div class="insight-outlet-header">
        <div><h2 id="branding-outlet-title" data-insight-title>Daftar Outlet</h2><p id="branding-outlet-description" data-insight-description></p></div>
        <button type="button" class="insight-outlet-close" data-insight-close aria-label="Tutup daftar outlet"><i class="fas fa-times" aria-hidden="true"></i></button>
    </div>
    <div class="insight-outlet-controls">
        <label class="insight-outlet-search"><span>Cari outlet atau sales</span><input type="search" data-insight-filter="search" placeholder="ID outlet, nama, atau SF…" autocomplete="off"></label>
        <label><span>TAP</span><select data-insight-filter="tap"><option value="">Semua TAP</option></select></label>
        <label><span>Status visit</span><select data-insight-filter="visit"><option value="">Semua Visit</option><option value="visited">Sudah Visit</option><option value="unvisited">Belum Visit</option></select></label>
        <label><span>Dominan branding</span><select data-insight-filter="brand"><option value="">Semua Branding</option><option>Tsel</option><option>Indosat-Tri</option><option>XL-Smart</option><option>No Branding</option><option value="missing">Belum Diisi</option></select></label>
        <label><span>Kelayakan branding</span><select data-insight-filter="quality"><option value="">Semua Kelayakan</option><option>Bagus</option><option>Kurang Bagus</option><option>Tidak Bagus</option><option value="missing">Belum Diisi</option><option value="not_applicable">Tidak Berlaku</option></select></label>
    </div>
    <div class="insight-outlet-caption"><strong data-insight-count aria-live="polite"></strong><span>Sudah visit berdasarkan foto yang diunggah; persetujuan ditampilkan terpisah.</span></div>
    <div class="insight-outlet-table-wrap">
        <table class="insight-outlet-table"><thead><tr><th scope="col">OUTLET</th><th scope="col">SALES FORCE</th><th scope="col">TAP</th><th scope="col">VISIT</th><th scope="col">DOMINAN BRANDING</th><th scope="col">KELAYAKAN</th><th scope="col">LOKASI</th></tr></thead><tbody></tbody></table>
        <div class="insight-outlet-empty" data-insight-empty hidden><i class="fas fa-magnifying-glass" aria-hidden="true"></i><strong>Tidak ada outlet sesuai filter</strong><p>Coba ubah pencarian atau pilihan filter.</p></div>
    </div>
    <div class="insight-outlet-footer">
        <span data-insight-summary aria-live="polite"></span>
        <label>Per halaman <select data-insight-size aria-label="Jumlah outlet per halaman"><option>20</option><option>50</option><option>100</option></select></label>
        <nav aria-label="Halaman daftar outlet"><button type="button" data-insight-previous aria-label="Halaman sebelumnya"><i class="fas fa-chevron-left" aria-hidden="true"></i></button><span data-insight-page></span><button type="button" data-insight-next aria-label="Halaman berikutnya"><i class="fas fa-chevron-right" aria-hidden="true"></i></button></nav>
    </div>
</dialog>
