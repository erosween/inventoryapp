# Koordinat outlet SURYA

Koordinat berasal dari GPS perangkat SF saat mengambil foto depan/check-in. Nama TAP
atau posisi SF yang diperkirakan tidak dipakai untuk menebak lokasi outlet.

## Alur

1. Form meminta GPS baru (`maximumAge: 0`) saat mengambil foto. GPS foto depan
   disimpan bersama hasil foto dan dipakai sebagai titik check-in. Tombol Simpan
   memakai snapshot tersebut; perpindahan SF setelah foto tidak mengganti titik.
2. Jarak GPS foto depan dibandingkan dengan titik outlet valid/disetujui terbaru,
   termasuk saat master lama belum disinkronkan. Jarak maksimal 10 meter langsung
   `valid` dan menyinkronkan master outlet di transaksi yang sama.
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
9. Setelah ditolak, SF mengambil ulang kedua foto. GPS foto depan, path foto,
   waktu visit, dan status diperbarui; timer approval otomatis 15 detik dimulai
   dari foto ulang. Titik lama bertahan sampai visit ulang disetujui, lalu master,
   peta, pencarian, dan navigasi mengikuti GPS foto ulang.

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
- Cross-check alur foto ulang: test HTTP dari unggah dua foto jauh, reject,
  unggah ulang dengan GPS berbeda, status pending sebelum 15 detik, dan approval
  manual/otomatis. Keduanya memperbarui master dan pencarian ke GPS foto ulang.
  Test frontend juga menjalankan handler Simpan untuk memastikan GPS foto depan
  tidak tertimpa posisi SF setelah foto. Validasi cross-check: 45 test aplikasi
  dan 6 test GPS frontend lolos.

## Upload foto di koneksi mobile

- Selama kamera terbuka, `watchPosition` menyiapkan pembacaan GPS presisi.
  Foto boleh memakai pengukuran perangkat yang masih berumur maksimal 10 detik;
  bila lebih lama, browser wajib memperoleh pengukuran baru. GPS master outlet
  tidak digunakan sebagai fallback. Watch dihentikan saat kamera ditutup atau
  halaman ditinggalkan, dan callback watch lama diabaikan.
- Pembacaan sekali jalan yang bersamaan memakai satu promise. Timeout atau lokasi
  sementara tidak tersedia dicoba ulang sekali (tetap high accuracy dan
  maximumAge 0), dengan batas tunggu 12 detik lalu 10 detik. Izin ditolak tidak
  diulang otomatis dan membatalkan penggunaan pengukuran terakhir. UI membedakan
  izin diblokir, timeout, lokasi tidak tersedia, dan GPS lama/tidak valid.
- Tombol foto dikunci selama pembacaan lokasi/pengambilan foto untuk mencegah
  permintaan yang bertabrakan. Timeout tidak menghapus foto yang sudah berhasil
  diambil, dan tombol dapat dicoba lagi. Lokasi simpan tetap GPS foto depan.

- Foto kamera tetap maksimum 1.600 piksel. JPEG dimulai pada kualitas 76%;
  hanya foto di atas 320 KiB yang dicoba pada kualitas 64% lalu 56%. Ini sasaran
  ukuran, bukan batas mutlak; resolusi dipertahankan agar detail outlet terbaca.
- Upload memakai XHR dengan progres transfer. Setelah 100%, tampil status
  penyimpanan server; navigasi baru dilakukan setelah respons JSON sukses.
- Batas tunggu respons 120 detik. Kesalahan koneksi/validasi tidak memuat ulang
  halaman atau menghapus input foto. Tidak ada pengiriman ulang otomatis;
  tautan Cek Status Visit tersedia untuk memeriksa hasil yang belum pasti.
- Server tetap memvalidasi foto gelap dan menyimpan WebP serta thumbnail. Encoder
  Imagick memakai method 4. Uji sintetis lokal 1.600 × 1.000 piksel: method 6
  158 ms/70.140 byte, method 4 105 ms/75.830 byte, pada kualitas sama (72%).
  Ini bukan pengukuran latency VPS ataupun jaringan sales.
- Query GPS mendapat indeks `(id_outlet, gps_status, visited_at)` agar lookup
  visit valid/approved per outlet tidak harus memindai seluruh riwayat.
- Respons sukses memuat `Server-Timing`. Proses controller yang mencapai 3 detik
  dicatat dengan pesan `SURYA visit save slow`: lookup_ms, photos_ms, save_ms,
  total_ms, upload_bytes, SF dan outlet. Waktu transfer sebelum PHP menerima
  request tidak termasuk durasi ini; progres browser membantu membedakannya.
- Deploy memerlukan `php artisan migrate --force` dan `php artisan optimize:clear`
  (jalankan sebagai user aplikasi, biasanya `www-data`).
