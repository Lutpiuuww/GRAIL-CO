# Kesesuaian dengan modul UTS Web Programming 2

Sumber: `UTS_Integrated_Laravel_Project_Final_Desain_Baru.pptx` yang diberikan dosen, terutama slide 4–13, 16–22, dan 25. Dokumen ini mencatat keadaan project pada 21 Oktober 2026. Role customer adalah tambahan atas instruksi langsung dosen; modul menuliskan admin dan staff sebagai role minimal, bukan larangan role lain.

| Kebutuhan modul | Bukti pada project | Status |
|---|---|---|
| Login/logout, hashing, middleware role | Auth, middleware server, konfirmasi logout, test role | Ada |
| Admin CRUD kategori/produk/supplier serta users | Back office dan routes admin | Ada |
| Staff Stock In dan Sales multi-item | Purchase/Sale controller, form transaksi, nota | Ada |
| SKU unik, harga/jumlah valid, stok cukup | Form request/validasi, unique constraint, stock lock | Ada |
| Header, item, stok dalam DB transaction; rollback | Controller transaksi dan test rollback/concurrency | Ada |
| Produk yang memiliki riwayat tidak dihapus permanen | Soft delete produk; nota tetap membaca arsip | Ada |
| Dashboard agregat, stok rendah | Dashboard admin/staff dari query database | Ada |
| Search, filter tanggal/status, pagination | Daftar produk dan riwayat transaksi | Ada |
| Relasi Eloquent, FK, migration, seeder, ERD | `app/Models`, `database/migrations`, `database/seeders`, `docs/ERD.md` | Ada |
| Pesan validasi, CSRF, escaping Blade | Form request, middleware web, output Blade | Ada |
| Test skenario minimum | `tests/Feature`, `docs/UTS_TEST_MATRIX.md`; terbaru 59 test/608 assertions pada SQLite; 55 test/566 assertions pada MariaDB QA sebelum fitur wilayah/foto/arsip | Ada; perubahan terakhir belum diuji ulang di MariaDB |
| Instalasi dari repository baru dengan MySQL | Langkah di README dan `.env.example` memakai MySQL; `migrate --seed --force` berhasil pada database MariaDB QA baru dengan 3 user, 5 kategori, 4 supplier, 8 produk | Migrasi dan seeder lulus; clone/dependency install bersih belum diuji |
| Source code di GitHub | Repository Git lokal sudah dibuat; remote/unggahan GitHub belum ada | Belum |
| Screenshot halaman utama dan transaksi | 24 screenshot login, dashboard, produk, Stock In, Sales, nota, validasi stok, customer, alamat, dan admin pada `docs/screenshots/` | Ada; preview cetak belum diperiksa |
| Demo dan wawancara kepemilikan kode | Alur dan pertanyaan dosen diketahui dari modul | Perlu latihan |

## Fokus penyelesaian sebelum pengumpulan

1. Setelah repository GitHub tersedia, uji clone bersih: `composer install`, salin `.env.example`, konfigurasi DB, `php artisan key:generate`, `php artisan migrate --seed`, `npm install`, `npm run build`, dan `php artisan serve`. Migrasi serta seeder sudah lolos pada database MariaDB QA baru; sisa langkah perlu diuji dari clone. Gunakan database demo baru, bukan database kerja yang sudah memiliki transaksi.
2. Tinjau screenshot di `docs/screenshots/README.md` sebelum pengumpulan. Bukti yang ada berasal dari database demo terpisah; ulangi tangkapan bila data atau tampilan demo final berubah. Preview cetak masih perlu diperiksa.
3. Latih demo urutan Stock In → stok naik → Sale → stok turun → penjualan melebihi stok ditolak → void admin → stok pulih. Jelaskan lokasi validasi server, DB transaction, row lock, relasi, dan middleware.
4. Hubungkan repository lokal ke GitHub setelah tujuan repository tersedia. Periksa file yang akan diunggah dengan `git status`/`git check-ignore`; jangan unggah `.env`, database lokal, `vendor`, atau `node_modules`.

## Keputusan lingkup

Payment gateway, multi-warehouse, akuntansi penuh, mobile app terpisah, AI forecasting, ERP, dan multi-currency dinyatakan tidak wajib oleh modul. Fitur customer, katalog, cart, checkout, dan tracking tetap boleh ada sebagai tambahan; kualitas business rule Stock In/Sales tetap prioritas penilaian. Upload foto dan pemulihan arsip sudah tersedia; snapshot nama/SKU nota dan retur masih menjadi peningkatan opsional di luar syarat kelulusan minimal.
