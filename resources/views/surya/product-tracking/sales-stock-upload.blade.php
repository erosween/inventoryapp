<div class="pt-result-head">
    <div>
        <h2>Upload Stok SN Sales</h2>
        <p class="pt-subtitle">Unggah daftar serial yang saat ini dipegang Sales Force. Rentang SN disimpan per baris untuk persiapan pemilihan stok berikutnya.</p>
    </div>
    <a class="pt-button" href="{{ route('surya.product-tracking.sales-stock.template') }}"><i class="fas fa-download"></i> Unduh Template CSV</a>
</div>
<div class="pt-table-wrap pt-sales-stock-template">
    <table class="pt-table"><thead><tr><th>SFCODE</th><th>TAP</th><th>CLUSTER</th><th>IDDENOM</th><th>SN AWAL</th><th>SN AKHIR</th><th>QTY</th></tr></thead>
        <tbody><tr><td>—</td><td>—</td><td>—</td><td>Isi IDDENOM dari master denom</td><td>SN pertama</td><td>SN terakhir</td><td>Opsional, dihitung otomatis</td></tr></tbody>
    </table>
</div>
<p class="pt-muted"><strong>IDDENOM</strong> harus cocok dengan master denom. Stok PV memakai SN 12 digit; stok SA memakai SN 15 digit tanpa 0 depan (SN 16 digit dengan 0 depan juga diterima). Isi SN Awal dan SN Akhir sama untuk satu nomor. QTY boleh dikosongkan karena dihitung dari rentang; jika diisi, nilainya harus cocok.</p>
@if(auth()->user()?->isSuperAdmin())
    <form method="post" action="{{ route('surya.product-tracking.sales-stock.upload') }}" enctype="multipart/form-data" class="pt-grid pt-upload-form" data-loading-message="Memvalidasi data dan menyimpan rentang SN…" data-loading-label="Mengunggah…">
        @csrf
        <input type="hidden" name="kind" value="sales-stock">
        <div class="pt-field pt-upload-file-field"><label for="sales-stock-file">File stok SN Sales</label><div class="pt-file-control"><input class="pt-file-input" id="sales-stock-file" type="file" name="file" accept=".csv,.txt,.xlsx,.xls" required><label class="pt-file-action" for="sales-stock-file">Pilih file</label><span class="pt-file-name pt-muted" id="sales-stock-file-name">Belum ada file dipilih</span></div><small class="pt-file-hint">CSV atau Excel · Maksimal 100 MB · Header wajib sesuai template</small></div>
        <div class="pt-field pt-upload-submit"><button class="pt-button primary" type="submit"><i class="fas fa-upload"></i><span>Upload Stok</span></button><span class="pt-loading-status" role="status" aria-live="polite" hidden></span></div>
    </form>
@else
    <div class="pt-muted">Upload hanya tersedia untuk Admin.</div>
@endif
