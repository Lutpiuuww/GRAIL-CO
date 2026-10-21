# Matriks pengujian UTS

| Area | Skenario | Hasil yang diharapkan | Bukti otomatis |
|---|---|---|---|
| Role | Customer mengakses dashboard/master/Users | 403 | GrailFlowTest, UserManagementTest |
| Role | Staff mengubah master atau void transaksi | 403 | GrailFlowTest, TransactionAuditTest |
| Dashboard | Admin vs staff | Keuangan untuk admin, operasional untuk staff | AccountTest |
| Akun | Admin membuat dan mengubah role/status akun | Validasi email unik; password tersimpan hashed | UserManagementTest |
| Akun | Admin menonaktifkan/menurunkan role sendiri | Ditolak | UserManagementTest |
| Akun | Akun nonaktif login atau memakai sesi lama | Ditolak/sesi diakhiri; riwayat tetap ada | UserManagementTest |
| Profil | Ubah identitas/password | Butuh password saat ini; role tidak bisa disisipkan | AccountTest |
| Login | 5 kegagalan pada email/IP sama | Diblokir sementara, dapat mencoba setelah 60 detik | AccountTest |
| Input | Jumlah kosong/negatif/pecahan, produk duplikat/arsip | Validasi menolak tanpa menyimpan header/detail | InventoryIntegrityTest |
| Stok | Edit stok melalui CRUD produk | Ditolak; stok hanya berubah melalui transaksi | InventoryIntegrityTest |
| Checkout | Harga dimanipulasi di browser | Harga dihitung dari produk di server | GrailFlowTest |
| Checkout bertahap | POST konfirmasi tanpa draft review | Ditolak tanpa menyimpan transaksi | CheckoutFlowTest |
| Penerima | Kontak/alamat tidak valid untuk pengiriman | Validasi menolak; alamat tidak wajib untuk pickup | CheckoutFlowTest |
| Biaya | Browser mengirim total/ongkir/status paid palsu | Total/ongkir dihitung server; pembayaran tetap pending | CheckoutFlowTest |
| Review | Harga berubah atau jumlah cart berubah | Harus tinjau kembali; stok tidak berkurang | CheckoutFlowTest |
| Konfirmasi | Request diulang setelah cart dikosongkan | Mengarah ke pesanan sama, tidak membuat sale baru | CheckoutFlowTest |
| Privasi | Membuka halaman keberhasilan milik akun lain | 404 | CheckoutFlowTest |
| Revenue | Pesanan dengan pembayaran simulasi pending | Tidak dihitung sebagai omzet diterima | CheckoutFlowTest |
| Transaksi | Item kedua gagal karena stok kurang | Header/detail dan semua stok rollback | InventoryIntegrityTest |
| Void | Alasan kosong atau staff mencoba membatalkan | Ditolak | TransactionAuditTest |
| Void | Admin membatalkan transaksi | Stok berubah satu kali; aktor/waktu/alasan tercatat | TransactionAuditTest |
| Void Stock In | Stok tidak cukup untuk ditarik kembali | Stok dan audit tidak berubah | TransactionAuditTest, InventoryIntegrityTest |
| Nota | Customer membuka nota pengguna lain | 404 | TransactionAuditTest |
| Nota | Produk diarsipkan atau harga produk berubah | Nota tetap tampil dengan harga transaksi | TransactionAuditTest |
| Filter | Nomor, status, tanggal inklusif | Hanya transaksi yang sesuai; rentang terbalik ditolak | TransactionAuditTest |
| Cart | Ubah jumlah atau hapus produk terarsip | Validasi stok; produk terarsip dapat dibuang | InventoryIntegrityTest |
| Detail produk | Guest/customer membuka detail | Informasi publik dan harga jual tampil; harga beli tidak ditampilkan | ProductDetailTest |
| Detail produk | Produk habis/arsip | Habis dapat dilihat, tidak bisa dibeli; arsip 404 | ProductDetailTest |
| Cart | Tambah jumlah dari detail | Jumlah positif dan total cart tidak melebihi stok | ProductDetailTest |
| Kiriman ganda | Token sama dikirim ulang | Satu transaksi dan satu perubahan stok | TransactionSubmissionTest |
| Retry | Transaksi gagal lalu dicoba kembali dengan token sama | Percobaan gagal tidak menghabiskan token | TransactionSubmissionTest |
| Seeder | Seeder dijalankan ulang | Stok, arsip, role, status, password yang ada tidak direset | SeedIntegrityTest |
| Concurrency | Dua checkout berebut satu unit | Satu berhasil, satu ditolak, stok akhir nol | tests/Support/mysql-concurrency.php |
| Pembayaran | Staff/admin mencatat transfer; kiriman ulang versi lama | Satu pembayaran/audit; perubahan lama ditolak | OrderWorkflowTest |
| Progres | Transfer belum lunas / COD belum lunas | Transfer tidak bisa diproses; COD tidak bisa selesai | OrderWorkflowTest |
| Pengiriman | Lewati tahap atau resi/kurir kosong | Ditolak; stok tidak berubah saat progres | OrderWorkflowTest |
| Pickup | Lokasi/jadwal kosong atau masa lalu | Ditolak; waktu WIB dikonversi UTC | OrderWorkflowTest |
| Privasi workflow | Customer mengubah status atau melihat order orang lain | 403 / 404; actor internal tidak ditampilkan ke customer | OrderWorkflowTest |
| Void online | Sebelum kirim dibanding setelah kirim/siap diambil | Sebelum: stok kembali sekali/refund simulasi; setelah: ditolak | OrderWorkflowTest |
| Filter online | Pembayaran dan fulfillment | Hanya order sesuai filter; revenue mengikuti pembayaran | OrderWorkflowTest |
| POS | Transaksi kasir tanpa checkout token | Workflow online tidak tersedia; kompatibilitas terjaga | OrderWorkflowTest |
| Concurrency pembayaran | Dua petugas memakai versi sama | Satu paid, satu audit, versi lama ditolak | tests/Support/mysql-concurrency.php |

Pengujian HTTP default menggunakan SQLite in-memory. Uji MariaDB/MySQL memakai database khusus dengan nama berakhiran `_test`; jangan arahkan pengujian ke database demo/operasional karena RefreshDatabase membangun ulang skema pengujian.

Pemeriksaan browser sesi ini: halaman Users pada 375 dan 1440 px, detail nota pada 1024 px, form transaksi pada 375 dan 768 px; tombol tambah/hapus item diuji tanpa menyimpan transaksi. Foto dan kontrol jumlah cart diperiksa pada 375 px. Tabel lebar bergulir di dalam panel. Preview cetak/PDF dan alur interaksi lengkap setiap halaman belum diperiksa secara visual.

Hasil akhir backend: 35 test / 263 assertions lulus pada MariaDB XAMPP 10.4.32. Probe concurrency dua proses juga lulus. SQLite lulus 34 test / 257 assertions, dilengkapi test seeder tambahan yang lulus 1 test / 6 assertions. Build Vite, Pint, dan kompilasi Blade lulus.

Update setelah fitur detail/logout: SQLite 38 test / 298 assertions lulus; MariaDB belum diuji ulang untuk perubahan terbaru. Popup logout diuji melalui browser pada admin/staff dan layout Profile mobile; tombol batal mempertahankan sesi, tombol konfirmasi mengakhiri sesi. Data login customer yang telah berubah tidak direset untuk pengujian.

Update checkout bertahap: seluruh 45 test / 406 assertions lulus di SQLite dan MariaDB XAMPP 10.4.32. Probe dua proses memakai CheckoutController baru; satu checkout memperebutkan unit terakhir berhasil dan satu ditolak, stok akhir nol. Browser memverifikasi cart → checkout → tinjau, pengisian alamat contoh, perubahan pickup yang menyembunyikan alamat dan menghapus ongkir, serta layout review 375 px. Browser tidak mengonfirmasi pesanan pada database kerja; konfirmasi/success/nota diverifikasi melalui feature test pada database tes.

Update workflow 21 Oktober 2026: SQLite dan MariaDB XAMPP 10.4.32 masing-masing 55 test / 566 assertions lulus, termasuk 10 test workflow baru. Kedua probe concurrency lulus; build Vite, Pint, dan kompilasi Blade lulus. Browser QA terpisah menampilkan nota, timeline awal, form pembayaran, dan tombol proses yang terkunci untuk transfer pending. Timeout kontrol browser menghalangi penyelesaian interaksi pembayaran dan pemeriksaan visual mobile baru; jangan menyamakan feature test yang lulus dengan QA browser end-to-end.
