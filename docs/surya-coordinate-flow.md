# Koordinat outlet SURYA

Koordinat berasal dari GPS perangkat SF saat menyimpan dua foto visit. Nama TAP
atau posisi SF yang diperkirakan tidak dipakai untuk menebak lokasi outlet.

## Alur

1. Form meminta GPS baru (`maximumAge: 0`) saat mengambil foto dan menyimpan visit.
2. Visit berstatus `valid` langsung menyinkronkan master outlet di transaksi yang sama.
3. Visit `pending_admin` mengikuti approval manual atau aturan otomatis yang sudah
   ada (15 detik dan kedua foto lengkap). Semua jalur approval memakai layanan sama.
4. Master selalu mengikuti visit **valid/approved terbaru menurut `visited_at`**.
   ID hanya menjadi pembeda jika waktunya sama. Approval visit lama tidak boleh
   memundurkan lokasi, dan GPS tidak valid atau `(0,0)` tidak digunakan.
5. Daftar peta, pencarian/detail popup, titik monitoring, pusat kompetisi, dan
   pencarian terdekat memakai resolver koordinat yang sama. Riwayat yang sudah
   disetujui tetap terbaca meskipun master lama belum disinkronkan.
6. Hasil pencarian memperbarui posisi marker beserta clusternya. Cache rincian
   dibatasi 30 detik; pencarian baru selalu memakai koordinat dari server.
7. Hijau menandai visit terbaru valid/disetujui dengan dua foto dan GPS yang
   dipakai oleh titik peta. Foto pada visit ditolak/pending tidak membuat marker
   hijau. Oranye menandai visit yang perlu diperiksa. Popup menampilkan status,
   sumber koordinat, dan tautan GPS visit terakhir, terpisah dari navigasi titik.
8. Form mengosongkan GPS sebelumnya sebelum membaca ulang, memeriksa timestamp
   GPS (maksimal 30 detik), dan mengabaikan callback permintaan lama yang datang
   terlambat. Koordinat serta akurasi ditampilkan agar SF bisa memeriksanya.

## Deploy dan perbaikan riwayat seluruh outlet

```bash
git pull origin inventoryapp
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan surya:sync-outlet-coordinates --dry-run
sudo -u www-data php artisan surya:sync-outlet-coordinates
```

Perintah terakhir memeriksa seluruh master outlet, hanya memperbarui koordinat
yang berbeda dari visit valid/approved terbaru, dan aman dijalankan ulang.
Outlet tanpa visit yang memenuhi syarat mempertahankan koordinat master.
Perintah sinkronisasi tidak mengubah status approval visit.

Pemeriksaan dua sampel di database VPS:

```bash
sudo -u www-data php artisan surya:sync-outlet-coordinates --dry-run --outlet=1201073824 --outlet=1201142452
```

Output menampilkan GPS master, visit terakhir beserta status/GPS-nya, visit yang
dipakai, dan jumlah outlet yang perlu diperbarui. Jika GPS visit sendiri masih
di lokasi lama, hasil GPS perangkat perlu diperiksa; server tidak mengarang
koordinat baru berdasarkan TAP.

## Validasi lokal 26 Oktober 2026

- Audit tanpa perubahan: 20.306 master, 5 punya visit valid/approved, 20.301 tanpa
  riwayat yang memenuhi syarat; tidak ada master lokal yang perlu diperbarui.
- Kedua ID sampel ada di master lokal, tetapi riwayat visit produksi belum ada di
  salinan lokal. Karena itu status dan GPS aktual kedua sampel di VPS belum dapat
  dikonfirmasi dari laptop.
- Regresi GPS menguji waktu visit versus urutan ID impor, approval visit lama,
  visit ditolak/pending/GPS invalid, waktu visit sama, simpan visit valid,
  polling mobile, sinkronisasi massal/dry-run/idempotensi, dan konsistensi map/search.
- Uji browser memakai fixture transaksi yang di-rollback di setiap request:
  marker yang awalnya di Pekanbaru dipindah hasil pencarian ke Bengkalis, termasuk
  tautan navigasi `1.470001,102.120001`; tidak ada fixture yang disimpan permanen.
- Suite luas awal: 97 lolos, 6 gagal pada test mobile lama. Keenamnya juga gagal
  saat memakai controller dari commit sebelum perbaikan. Penyebabnya ekspektasi
  URL timeout, alur receipt/foto struk dan tombol foto yang sudah berubah, serta
  tabel `surya_sa_consignment_movements` yang belum ada pada fixture SQLite.
- Regresi status marker dan sumber koordinat, beserta pembacaan GPS frontend:
  `php artisan test tests/Feature/SuryaOutletCoordinatesTest.php tests/Feature/SuryaOutletListTest.php tests/Feature/SuryaWorkspaceTest.php`
  (37 test lolos) dan `node --test tests/Unit/SuryaVisitGps.test.cjs` (3 test lolos).
