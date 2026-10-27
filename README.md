# GRAIL.CO

Aplikasi retail archive streetwear dan luxury horology berbasis Laravel 12, PHP 8.2+, Eloquent, Blade, Tailwind CSS 4, Vite, dan MySQL/MariaDB.

## Fitur

- Login dengan pembatasan percobaan, registrasi customer, konfirmasi logout semua role, profil dengan kontak/alamat utama tersimpan, dan penggantian password.
- Tiga role dilindungi middleware server: admin, staff, customer.
- Admin mengelola kategori, supplier, produk, serta akun pengguna. Akun dapat dinonaktifkan tanpa menghapus riwayat; admin tidak bisa menonaktifkan atau menurunkan role sendiri.
- Staff melihat produk/supplier serta mencatat Stock In dan penjualan kasir.
- Dashboard keuangan admin dan dashboard operasional staff.
- Katalog responsif, pencarian, filter kategori, delapan visual produk studio komersial ultra-realistis dalam format WebP.
- Detail produk publik melalui foto/nama/tombol Lihat detail, lengkap dengan deskripsi, stok, harga, pilihan jumlah, dan koleksi terkait.
- Admin dapat mengunggah, mengganti, atau menghapus foto produk. Foto yang didukung browser dipotong otomatis di tengah dengan rasio 4:5, diperkecil menjadi 800 × 1000 px, lalu dikirim sebagai WebP (atau PNG jika WebP tidak tersedia). Tidak ada batas dimensi foto asal; validasi server menerima JPG/PNG/WebP hingga 20 MB. Foto unggahan ditampilkan di katalog; produk tanpa foto unggahan memakai gambar bawaan jika tersedia.
- Admin dapat membuka arsip produk dan memulihkannya tanpa mengubah stok atau riwayat transaksi.
- Cart dengan ubah jumlah, penanda produk habis/terarsip, serta checkout.
- Customer dapat membatalkan pesanan sebelum pembayaran atau proses pesanan dikonfirmasi penjual; stok dikembalikan dan pembatalan dicatat.
- Checkout bertahap: data penerima, alamat utama dari profil atau alamat lain, pengiriman/pengambilan, metode pembayaran, tinjau rincian biaya, lalu konfirmasi pesanan dengan nomor nota.
- Biaya pengiriman yang ditetapkan aplikasi dihitung server: reguler Rp25.000, express Rp45.000, ambil di toko gratis. Tarif belum terhubung ke penyedia kurir. Pembayaran transfer/COD dimulai dengan status belum dikonfirmasi dan dicatat oleh petugas setelah verifikasi di luar aplikasi.
- Transaksi multi-item dinamis, harga server-side, DB transaction, row locking, validasi stok, dan proteksi kiriman ulang menggunakan token.
- Riwayat Stock In/Sales dengan pencarian nomor, filter tanggal/status, pagination, detail nota, dan tombol cetak melalui browser.
- Back office menampilkan jumlah pesanan menunggu dan pemberitahuan pesanan baru. Daftar Sales diperbarui otomatis tiap 5 detik selama halaman terbuka tanpa memuat ulang seluruh browser.
- Void khusus admin: alasan wajib, petugas/waktu tercatat, stok disesuaikan secara atomik.
- Customer hanya melihat riwayat dan nota miliknya sendiri; produk terarsip tetap terbaca pada nota.

## Instalasi baru di Windows PowerShell

Jalankan dari folder aplikasi hasil clone (`grail-co`). Siapkan PHP 8.2+ dengan ekstensi PDO MySQL/SQLite, Composer, Node.js/npm, dan server MySQL/MariaDB aktif. Project tidak bergantung pada lokasi `C:\Coding\GRAIL.CO`.

```powershell
composer install
npm.cmd install
Copy-Item .env.example .env
php artisan key:generate
```

Langkah menyalin .env hanya untuk instalasi baru. Untuk project yang sudah berjalan, pertahankan konfigurasi lokal yang ada.

Buat database melalui MySQL client atau phpMyAdmin:

```sql
CREATE DATABASE grail_co_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Isi konfigurasi .env sesuai server lokal:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=grail_co_db
DB_USERNAME=root
DB_PASSWORD=
```

Kemudian:

```powershell
php artisan migrate --seed
npm.cmd run build
php artisan serve
```

Buka alamat yang ditampilkan Artisan. Untuk frontend development gunakan `npm.cmd run dev` di terminal terpisah.

## Memperbarui instalasi yang sudah ada

```powershell
php artisan migrate
npm.cmd run build
```

Migrasi menambah kolom audit, status akun, tabel submission, dan lokasi foto unggahan. Gunakan migrate biasa; migrate:fresh menghapus tabel. Seeder menambah data awal yang belum ada dan mempertahankan stok, password, role, status, serta produk terarsip yang sudah ada.

Foto yang diunggah admin tersimpan di `public/images/products/uploads` dan tidak masuk Git. Jika memindahkan instalasi dengan foto unggahan, cadangkan folder tersebut bersama database. Dataset dropdown wilayah tersedia secara lokal di `resources/data/indonesia-regions.json`; asal dan cara memperbaruinya ada di [catatan data wilayah](resources/data/README.md).

Instalasi kerja saat ini tetap SQLite. Pengujian MariaDB dilakukan pada instance dan database QA terpisah; .env aplikasi tidak dipindahkan otomatis ke MySQL.

## Akun awal

| Role | Email | Password awal |
|---|---|---|
| Admin | admin@grail.co | password |
| Staff | staff@grail.co | password |
| Customer | customer@grail.co | password |

Password di atas berlaku saat akun pertama dibuat oleh seeder. Perubahan password/status setelahnya dipertahankan. Kelola pengguna melalui Back Office → Users; ubah password milik sendiri melalui Profile.

## Hak akses

| Modul | Admin | Staff | Customer |
|---|---|---|---|
| Dashboard | Keuangan + stok | Operasional + stok | Ditolak |
| Kategori | CRUD | Ditolak | Ditolak |
| Produk / supplier | CRUD | Baca | Ditolak |
| Stock In / Sales | Buat, baca, void | Buat, baca | Ditolak |
| Users | Buat, ubah role/status | Ditolak | Ditolak |
| Katalog / cart / checkout | Akses | Akses | Akses |
| My Orders / detail nota | Milik sendiri | Milik sendiri | Milik sendiri |
| Profil | Milik sendiri | Milik sendiri | Milik sendiri |

## Pengujian

```powershell
php artisan test
npm.cmd run build
```

Pengujian default memakai SQLite in-memory. Untuk MySQL/MariaDB, buat database QA berakhiran `_test` dan arahkan environment terminal pengujian ke database tersebut:

```powershell
$env:APP_ENV='testing'
$env:DB_CONNECTION='mysql'
$env:DB_HOST='127.0.0.1'
$env:DB_PORT='3306'
$env:DB_DATABASE='grail_co_uts_test'
$env:DB_USERNAME='root'
$env:DB_PASSWORD='isi-password-server-uji'
php artisan test
php tests/Support/mysql-concurrency.php
```

Jalankan pada terminal pengujian terpisah. RefreshDatabase membangun ulang database tes; gunakan database khusus yang tidak berisi data penting. Probe concurrency menjalankan dua proses PHP yang memakai checkout aplikasi dan memperebutkan satu unit. Fixture probe ditinggalkan di database QA untuk inspeksi.

## Dokumentasi

- [ERD dan relasi](docs/ERD_GRAIL.CO.png)
- [Bukti screenshot halaman dan transaksi](docs/screenshots/README.md)

Nota menggunakan harga yang tersimpan di detail transaksi. Nama/SKU masih mengikuti data produk saat ini; identitas pembeli offline belum dipisahkan dari petugas kasir. Tombol cetak memakai dialog cetak browser, bukan generator PDF server.

## Alur checkout customer

1. Dari cart pilih **Lanjut ke checkout**.
2. Nama dan nomor telepon terisi dari profil bila tersedia. Untuk pengiriman, gunakan alamat utama yang sudah lengkap di profil atau pilih **Kirim ke alamat lain** untuk mengisi provinsi, kota/kabupaten, alamat jalan, dan kode pos lima digit. Untuk ambil di toko, alamat tidak diminta.
3. Pilih metode pengiriman dan pembayaran, lalu tinjau pesanan. Halaman ini belum mengurangi stok atau membuat transaksi.
4. Periksa subtotal, biaya pengiriman, total, dan data penerima. Konfirmasi melalui tombol **Buat pesanan**.
5. Halaman keberhasilan menampilkan nomor pesanan, biaya, data penerima, dan tautan nota/My Orders. Data ini hanya dapat dibuka oleh pemilik pesanan; admin/staff melihat data melalui Sales.

Harga atau isi cart yang berubah setelah ditinjau memerlukan peninjauan ulang. Stok diperiksa dengan row lock pada konfirmasi terakhir. Kiriman ulang token yang sudah selesai kembali ke pesanan yang sama, termasuk jika cart sudah kosong.

Pesanan baru memakai status pembayaran `pending` dan tampilan **Menunggu konfirmasi**. Pembayaran yang belum dikonfirmasi tidak masuk metrik Revenue. Belum ada payment gateway, penagihan otomatis, booking kurir, atau notifikasi email. Instruksi dan verifikasi pembayaran serta pengiriman ditangani di luar aplikasi.

Customer dapat membatalkan pesanan melalui halaman sukses, My Orders, atau detail nota selama pembayaran dan progres pesanan masih `pending`. Aplikasi memeriksa status terkini dalam transaksi database, mengembalikan stok satu kali, lalu mencatat pembatalan. Setelah penjual mencatat pembayaran atau memulai proses, tombol pembatalan customer tidak tersedia.

## Pengelolaan pesanan setelah checkout

Admin/staff membuka **Sales → Detail nota → Kelola pesanan customer**. Setelah memverifikasi pembayaran di luar aplikasi, catat konfirmasinya beserta referensi, lalu mulai proses pesanan. Transfer harus tercatat lunas sebelum diproses; COD boleh diproses terlebih dahulu tetapi harus lunas sebelum selesai.

- Pengiriman: menunggu konfirmasi → diproses → dikirim (kurir dan nomor resi wajib) → selesai.
- Pickup: menunggu konfirmasi → diproses → siap diambil (lokasi dan jadwal WIB wajib) → selesai.
- Customer melihat status, detail pengiriman/pickup, dan timeline melalui **My Orders → detail nota**. Catatan operator pada form ditampilkan kepada customer; jangan isi informasi internal atau rahasia.
- Pembayaran dan perubahan status menyimpan aktor, waktu, serta audit event. Versi pesanan dan database lock menolak pembaruan halaman lama/bersamaan tanpa menimpa data terbaru.
- Admin dapat void sebelum barang dikirim/siap diambil; stok kembali satu kali. Jika pembayaran sebelumnya tercatat lunas, aplikasi menandai pengembalian yang perlu diproses di luar aplikasi. Retur setelah penyerahan belum tersedia.

Resi, status pembayaran, dan pengembalian adalah pencatatan internal; aplikasi tidak terhubung dengan kurir atau memindahkan dana. Status transaksi `completed` berarti pencatatan sale aktif; progres customer memakai `fulfillment_status` terpisah. Transaksi kasir/lama tidak dipaksa mengikuti workflow online.

