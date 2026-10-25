# Audit frontend SURYA — 26 Oktober 2026

## Cakupan

Peta Outlet, Detail Visit (rekap/non-Digipos/approval), Detail Outlet (Digipos/non-Digipos), Stock Gudang, Stock on Hand (PV Reguler/SA), Input Target, Upload Transaksi, Approval Visit, dan Laporan Lapangan (konsinyasi/survei/jaringan).

## Penyebab dan perbaikan

| Temuan | Dampak | Perbaikan |
| --- | --- | --- |
| Header memiliki beberapa markup dan aturan alignment | Judul sistem bergeser ke kanan atau hilang pada menu tertentu | Semua halaman memakai `surya.partials.header`; layout grid menjaga teks sistem di tengah, dengan tombol menu di kiri. |
| Sidebar mempunyai beberapa pengatur JS dan breakpoint 640/900/960/1100 yang berbeda | Tombol menu hilang, margin kosong, preferensi collapse tidak konsisten | Satu pengatur `surya-shell.js`, breakpoint 1100, state desktop tersimpan, drawer mobile, backdrop, Escape, dan fokus keyboard. |
| CSS tabel/search/font disisipkan dalam partial sidebar | Peta, tabel, dan formulir saling menimpa | Sidebar hanya berisi navigasi. CSS shell, sidebar, dan halaman dipisahkan; CSS Stock/Input dibatasi ke halaman tersebut. |
| Stock/Input mempunyai penutup elemen berlebih dan header tersendiri | Browser memperbaiki DOM dengan struktur yang tidak sesuai | Kedua halaman memakai layout workspace yang sama dan satu judul halaman. |
| Stock Gudang mengarah ke layout Inventory | Pengguna kehilangan konteks dan navigasi SURYA | Endpoint `/surya/stock-gudang` memakai tabel/query stok yang sama dalam layout SURYA; halaman Inventory lama tetap tersedia. |
| CSS lama menang karena selector khusus dan `!important` | Warna tabel, ukuran font, sidebar, dan header berbeda | Aturan shell lama dibersihkan; font, header, judul, jarak konten, dan warna tabel diselaraskan. |
| Klik menu dashboard masih melakukan navigasi anchor penuh | Reload berlebih dan inisialisasi peta terulang | Tab dashboard mengubah panel/history tanpa reload; judul tab browser ikut berubah. |
| Peta dashboard lama tetap diinisialisasi ketika panel tersembunyi | Request tile dan batas wilayah yang tidak dipakai | Peta lama diinisialisasi hanya jika panelnya aktif; kontrol navigasi tetap dapat ditampilkan di atas peta fullscreen. |
| Riwayat pencarian langsung di-parse dari localStorage | Data storage rusak dapat menghentikan seluruh skrip dashboard | Parsing diberi fallback array kosong. |
| Target editor memakai `firstWhere` dengan callback dan query builder `whereKey` | Lookup target keliru dan penyimpanan berpotensi gagal | Lookup memakai `first`, simpan memakai kolom `id`, target tidak ditemukan menghasilkan 404. |
| Filter non-Digipos lebih spesifik daripada aturan mobile | Formulir melebar pada HP | Grid filter disesuaikan pada breakpoint yang sama; tabel lebar tetap scroll di dalam kartu. |
| Modal outlet berada di bawah header/sidebar | Dialog dapat tertutup navbar | Lapisan dialog dan photo viewer dinaikkan. |

Asset CSS/JS lokal diberi versi berdasarkan waktu perubahan file untuk menghindari cache browser yang mempertahankan tampilan lama.

## Verifikasi

- Render HTTP untuk 13 variasi halaman: status 200, satu header, satu sidebar, satu tombol menu, judul dan menu aktif sesuai halaman.
- Browser tersembunyi pada viewport CSS sekitar 320, 487, 1062, dan 1600 px: navigasi terlihat, drawer buka/tutup, tidak ada halaman yang melebar melewati viewport pada matriks yang diperiksa.
- Collapse desktop tersimpan ketika berpindah Stock on Hand → Input Target → Upload Transaksi; navbar tetap dapat dibuka kembali.
- Peta lokal memuat 3.318 outlet; tab Detail Visit dapat dibuka dari sidebar; tidak ditemukan error/warning konsol pada pemeriksaan alur tersebut.
- Pencarian SF, hasil kosong, pengembalian hasil, pagination Stock on Hand, dan filter Stock Gudang SF diperiksa melalui browser.
- Freeze header Stock on Hand diverifikasi ketika tabel di-scroll: posisi header sama dengan sisi atas scroll container dan navbar tetap di atas.
- Target sementara untuk pemeriksaan tabel berisi data dibuat hanya di database lokal dan dibersihkan sesudahnya. Tidak ada fixture atau jalur login audit yang dimasukkan ke repository.
- Test regresi SURYA dan stok dijalankan, termasuk isolasi role admin/cluster serta simpan target tanpa mengubah target lainnya.
- Hasil test gabungan: 42 lulus, 5.807 assertion.
- Semua stylesheet SURYA di-parse, JavaScript inline hasil render diperiksa syntax-nya, dan Blade view cache berhasil dibuat.

## Deploy VPS

Jalankan pembuatan cache dengan user PHP-FPM agar tidak mengulang error `Permission denied` pada `storage/framework/views` yang muncul dalam log production.

```bash
cd /var/www/inventoryapp
git pull origin inventoryapp
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan view:cache
sudo systemctl reload php8.3-fpm
```

Perbaikan frontend diverifikasi pada server lokal. Pemeriksaan production setelah pull tetap diperlukan; audit ini tidak menyatakan seluruh kemungkinan bug aplikasi sudah hilang.
