# Status pengerjaan UTS

## Perbaikan 20 Oktober 2026

- Relasi detail transaksi tetap memuat produk terarsip.
- Void sale/purchase dapat menyesuaikan stok produk terarsip.
- Kategori yang memiliki produk terarsip tidak bisa dihapus.
- Validasi transaksi menolak baris setengah terisi, jumlah tidak valid, produk duplikat, dan produk terarsip.
- Form produk tidak lagi mengirim stok; server menolak perubahan stok melalui CRUD produk. Produk baru dimulai dengan stok nol; penerimaan dicatat melalui Stock In.
- Nomor transaksi memakai ULID dengan unique constraint yang sudah ada.
- Checkout memvalidasi jumlah dan mengunci produk dalam urutan ID.
- Produk terarsip dalam cart tetap terlihat dan bisa dihapus; checkout memberikan pesan validasi.
- Test regresi mencakup rollback multi-item, pembatalan berulang, arsip produk, serta penolakan perubahan stok melalui HTTP.

## Prioritas berikutnya

Daftar lama ini sudah ditangani pada lanjutan di bawah. Pekerjaan yang masih terbuka:

1. Memindahkan konfigurasi dan data instalasi kerja dari SQLite ke MySQL apabila diperlukan untuk demo. Saat ini kompatibilitas diuji pada MariaDB XAMPP terpisah; database kerja belum dipindahkan.
2. Pemeriksaan preview cetak/PDF dan seluruh alur browser per role; verifikasi saat ini mencakup halaman dan interaksi utama yang disebutkan di bawah.
3. Pengembangan tambahan bila dibutuhkan rubrik: upload foto produk, halaman pemulihan arsip, identitas pembeli kasir terpisah, dan snapshot nama/SKU pada nota. Fitur tersebut belum tersedia.

## Lanjutan: akun dan dashboard

- Halaman `/profile` dapat mengubah nama/email milik pengguna yang sedang login, dengan konfirmasi kata sandi saat ini. Role tidak dapat diubah lewat profil.
- Penggantian kata sandi memerlukan kata sandi lama dan konfirmasi kata sandi baru.
- Login dibatasi setelah lima kegagalan per email/IP selama 60 detik; batas request tambahan melindungi login, registrasi, dan perubahan profil.
- Navigasi Profile customer tidak lagi mengarah ke katalog. Logout tersedia di halaman profil dan navigasi mobile back office; kategori admin juga dapat dijangkau di mobile.
- Dashboard admin menampilkan metrik keuangan; dashboard staff menampilkan metrik operasional tanpa menghitung/mengirim metrik keuangan ke view.
- Regresi cart produk terarsip mencakup tampilan dan penghapusan.
- Verifikasi akhir: `php artisan test` — 23 tests, 129 assertions, semua lulus. `npm.cmd run build` berhasil. Pint untuk file PHP yang disentuh berhasil.
- Tampilan belum diperiksa secara visual di browser pada ukuran layar berbeda. Pengujian masih SQLite; belum membuktikan concurrency MySQL. Data lokal tidak direset.

Daftar audit sebelumnya mencakup usulan tambahan di luar blueprint (payment gateway, pengiriman, Docker, dan sebagainya). Prioritas kelulusan ditentukan oleh blueprint/rubrik UTS. Keberadaan service class atau Policy bukan syarat otomatis keamanan; otorisasi server dan hasil pengujian perilaku tetap harus diperiksa.

## Lanjutan: Users, audit, nota, transaksi, dan QA

- `/users` khusus admin: tambah pengguna, ubah identitas/role/status, pencarian, filter role/status, dan pagination. Admin tidak dapat menurunkan role atau menonaktifkan diri. Tidak ada penghapusan akun agar relasi riwayat terjaga.
- Akun nonaktif tidak dapat login; sesi akun nonaktif diakhiri pada request berikutnya dan remember token dihapus saat dinonaktifkan.
- Void wajib alasan 5–1000 karakter. `cancelled_by`, `cancelled_at`, dan `cancellation_reason` disimpan atomik bersama perubahan stok. Void kedua ditolak; void gagal tidak meninggalkan catatan pembatalan.
- Nota Stock In/Sales dan nota milik customer tersedia, termasuk produk terarsip dan catatan pembatalan. Harga diambil dari detail transaksi. Pembatalan lama ditandai tanpa mengarang data audit.
- Riwayat Stock In/Sales memiliki pencarian nomor dan filter status/tanggal inklusif, validasi rentang, empty state, serta pagination.
- Form transaksi dapat menambah/menghapus baris sampai 100 item. Cart menampilkan foto produk, ubah jumlah, serta penjelasan produk habis/terarsip.
- `TransactionSubmission` dan tabel pendukung melindungi Stock In/Sales/checkout dari kiriman ulang dengan UUID yang sama. Token dan transaksi dicatat dalam transaksi database yang sama; akun dikunci untuk menangani permintaan bersamaan. Form yang dirender sebelum pembaruan perlu dimuat ulang agar membawa token.
- Seeder tidak lagi mereset stok, password, role, status akun, atau produk terarsip yang sudah ada.
- README diperbarui; `docs/ERD.md` dan `docs/UTS_TEST_MATRIX.md` ditambahkan.
- SQLite: 34 test/257 assertions lulus sebelum penambahan test seeder; test seeder tambahan 1 test/6 assertions juga lulus. MariaDB XAMPP 10.4.32: seluruh 35 test/263 assertions lulus setelah perubahan terakhir.
- Probe dua proses PHP pada MariaDB: satu unit diperebutkan dua checkout, satu sale tersimpan, checkout lain ditolak, stok akhir nol. Ini menguji checkout melalui controller aplikasi dengan koneksi/proses terpisah.
- Pint dan kompilasi Blade lulus. Build Vite berhasil.
- Browser: Users pada 375/1440 px, nota pada 1024 px, form transaksi pada 375/768 px; tombol tambah/hapus baris diuji. Foto dan kontrol jumlah cart diperiksa pada 375 px. Tabel tetap bergulir dalam panel.
- Migrasi lokal diterapkan dengan `migrate`, tidak menggunakan `migrate:fresh` dan tidak menjalankan seeder pada database kerja. Data transaksi lokal tetap dipertahankan.
- QA MariaDB menggunakan datadir terpisah `storage/app/mysql-qa-20261020`, port 3308, database `grail_co_uts_test`. Instance QA dihentikan setelah pengujian; fixture/direktorinya disimpan untuk inspeksi. `.env` aplikasi tetap SQLite.

Data lokal tidak direset dalam perbaikan ini. Jangan menggunakan migrate:fresh pada database berisi transaksi yang perlu dipertahankan.

## Update: konfirmasi logout dan detail produk

- Semua form logout pada layout storefront, back office (desktop/mobile), dan Profile memakai dialog bersama bertema obsidian/champagne. Fokus awal berada pada tombol batal, Escape menutup dialog, dan logout baru dikirim setelah tombol "Ya, keluar" ditekan. Browser tanpa dukungan dialog memakai konfirmasi bawaan. Konfirmasi membutuhkan JavaScript aktif.
- Halaman publik `/catalog/{product}` menampilkan gambar besar, nama, SKU, kategori, harga jual, deskripsi, stok, pilihan jumlah, serta koleksi terkait. Foto, nama, tombol Lihat detail di katalog, dan nama produk aktif dalam cart mengarah ke detail.
- Produk habis tetap dapat dilihat melalui URL detail, namun pembelian dinonaktifkan dan server menolak penambahan. Produk terarsip mengembalikan 404.
- Penambahan cart mendukung jumlah pilihan dengan validasi bilangan bulat positif dan total jumlah yang sudah ada di cart. Penambahan tidak mengubah stok gudang; checkout tetap memvalidasi stok kembali.
- ProductDescriptionSeeder mengisi ulang hanya deskripsi placeholder asli delapan produk demo, tanpa mengubah deskripsi khusus admin, stok, akun, atau transaksi. Seeder khusus deskripsi diterapkan ke data lokal; seeder penuh tidak dijalankan.
- Verifikasi terbaru: SQLite 38 tests / 298 assertions lulus, Pint dan kompilasi Blade lulus, build Vite berhasil. Pembaruan ini belum dijalankan ulang di MariaDB; hasil MariaDB 35 tests di bagian sebelumnya adalah hasil sebelum fitur ini.
- Browser: popup admin/staff diuji, batal dan konfirmasi keluar diverifikasi. Layout storefront/Profile memakai komponen yang sama untuk semua role; popup Profile dan detail produk diperiksa pada 375 px. Login customer demo tidak berhasil memakai password awal, sehingga kredensial/status akun yang sudah berubah dibiarkan; akses detail customer diverifikasi pada feature test.

## Update: checkout customer bertahap

- Cart kini mengarahkan ke form checkout, bukan langsung menyimpan sale. Alur menjadi data penerima → tinjau pesanan → halaman keberhasilan dan nota.
- Nama penerima, telepon, alamat/kota/provinsi/kode pos untuk pengiriman, serta catatan opsional divalidasi. Pickup tidak menyimpan alamat sisa dari metode pengiriman.
- Pilihan pengiriman: reguler Rp25.000, express Rp45.000, pickup Rp0. Semua tarif dan pembayaran transfer/COD bersifat simulasi UTS, bukan layanan nyata.
- Draft review berada di session. Total dihitung server dengan nilai sen integer, token berubah jika harga/ongkir berubah, dan final request tidak dapat mengganti harga/metode/data penerima dari browser.
- Cart yang berubah perlu ditinjau ulang. Stok/harga diperiksa kembali dalam transaksi dengan product locks pada konfirmasi akhir; kegagalan mempertahankan cart dan tidak membuat header/detail/token parsial.
- Sale online menyimpan checkout token unik, snapshot penerima/alamat, metode, ongkir, dan pembayaran pending. POST token yang sudah selesai kembali ke konfirmasi milik pengguna, tanpa membuat sale baru atau menghapus cart baru.
- Nota dan Sales menampilkan data checkout serta biaya ongkir terpisah. Pesanan ditampilkan Menunggu konfirmasi, bukan sebagai pembayaran lunas. Revenue mengecualikan pembayaran pending baru; transaksi lama/POS tetap diperlakukan seperti sebelumnya.
- Tujuh test baru mencakup validasi, harga/ongkir palsu, pickup, perubahan review, rollback stok, privasi konfirmasi, duplicate submit, dan revenue pending. Test checkout lama serta probe concurrency diperbarui memakai alur baru.
- Hasil: SQLite dan MariaDB XAMPP 10.4.32 sama-sama 45 tests / 406 assertions lulus; concurrency dua proses lulus. Pint, kompilasi Blade, dan build Vite berhasil.
- Migrasi penambahan kolom checkout diterapkan tanpa reset data. Browser memeriksa review desktop dan 375 px dengan data penerima contoh; pesanan tidak dikonfirmasi pada database kerja selama QA browser. Instance QA MariaDB dihentikan setelah tes; .env aplikasi tetap SQLite.
- Pada akhir tahap ini belum tersedia pengelolaan setelah checkout. Status terbaru ada pada bagian berikut.

## Update 21 Oktober 2026: operasional pesanan dan tracking customer

- Admin/staff dapat mencatat pembayaran simulasi (referensi wajib), memproses pesanan, memasukkan kurir/resi atau lokasi/jadwal pickup WIB, dan menandai selesai. Transfer wajib lunas sebelum proses; COD wajib lunas sebelum selesai.
- Customer melihat status aktual, detail pengiriman/pickup, dan timeline pada nota/My Orders. Catatan operator ditandai sebagai catatan untuk customer. Nama aktor hanya ditampilkan di back office.
- Status transaksi pencatatan tetap terpisah dari fulfillment. POS/lama tidak dipaksa mengikuti workflow online. Pesanan online yang sudah ada dimigrasikan tanpa membuat audit kejadian fiktif.
- Setiap pembayaran/progres/void dicatat atomik pada order_events. Row lock dan order_version menolak aksi ganda atau pembaruan halaman lama. Pergantian progres tidak mengurangi stok lagi.
- Void oleh admin hanya sebelum barang dikirim/siap diambil; stok kembali sekali dan pembayaran paid menjadi refunded secara simulasi. Retur setelah penyerahan belum tersedia.
- Migrasi lokal diterapkan dengan migrate biasa, tanpa reset atau seeder penuh. Database kerja dan kredensial pengguna dipertahankan. .env tetap SQLite.
- Verifikasi: SQLite dan MariaDB XAMPP 10.4.32 masing-masing 55 tests / 566 assertions lulus. Probe dua checkout memperebutkan satu unit serta dua petugas mencatat pembayaran bersamaan lulus: hanya satu aksi tersimpan pada setiap kasus. Pint, kompilasi Blade, dan build Vite lulus.
- Browser berhasil membuka nota dan kontrol operator pada database QA terpisah. Tombol proses dinonaktifkan saat transfer belum dibayar. Interaksi pembayaran selanjutnya terhambat timeout kontrol browser; alur browser end-to-end dan pemeriksaan visual mobile timeline belum dapat diklaim lulus. Perilaku lengkap diverifikasi melalui feature test.
- UI mengikuti tema obsidian/champagne yang sudah ada; skill ui-ux-pro-max digunakan untuk hierarchy form, timeline, dan penjelasan status/aksi.
- Batas: pembayaran/refund/kurir tetap simulasi, belum ada gateway, live tracking, notifikasi email, retur, atau permintaan pembatalan mandiri customer. Prioritas instalasi MySQL kerja, preview cetak, upload foto, dan arsip/pemulihan di atas masih terbuka.

## Pencocokan modul dosen, 21 Oktober 2026

- Modul UTS 28 slide telah dicocokkan dengan project. Role admin/staff memenuhi kebutuhan minimal; customer tetap dipertahankan sebagai tambahan sesuai arahan dosen. Fokus rubrik adalah workflow transaksi, stok, database, dan arsitektur Laravel. Rinciannya ada di `docs/UTS_REQUIREMENTS.md`.
- Repository Git lokal baru dibuat pada folder `grail-co`, tanpa commit atau remote GitHub. `.env`, database lokal, vendor, node_modules, dan build diabaikan oleh `.gitignore`; GitHub dan screenshot penyerahan belum selesai.
- README instalasi dibuat independen dari path komputer ini. Panduan demo dan lokasi jawaban kode ditambahkan di `docs/UTS_DEMO_GUIDE.md`.
- Migrasi serta seeder sukses dari database MariaDB QA yang baru dan kosong, menghasilkan 8 migration, 3 akun demo, 5 kategori, 4 supplier, dan 8 produk. Database kerja SQLite tidak diubah. Uji clone bersih, `composer install`, dan `npm install` dari repository baru masih perlu dilakukan setelah repository tersedia.
- Browser QA pada database SQLite demo terpisah berhasil menampilkan dan menyimpan 20 screenshot: login, dashboard admin/staff, produk, Stock In sukses, Sales sukses, nota, validasi stok kurang, customer checkout sampai sukses, riwayat pesanan, dan tracking mobile 375 px. Staff yang membuka URL Users mendapat 403. Data kerja tetap tidak dipakai untuk transaksi screenshot.
- Selama QA ditemukan nota/riwayat menampilkan waktu UTC sedangkan timeline WIB. Tampilan tanggal transaksi dan pembatalan disamakan ke WIB. Nota customer yang diperiksa ulang sekarang menunjukkan 16:06 WIB sama seperti timeline; `view:cache` dan 55 test/566 assertions lulus setelah perubahan.
