# Kasus uji dan hasil pengujian

Pengujian dijalankan pada 27 Oktober 2026 dengan PHP 8.2.12 dan SQLite in-memory sesuai `phpunit.xml`. Perintah `php artisan test --compact` menghasilkan **69 test lulus, 719 assertion**. Perintah `npm.cmd run build` juga berhasil. Ini membuktikan skenario otomatis pada lingkungan tersebut; untuk demo MySQL/MariaDB, jalankan migrasi dan uji ulang pada database khusus.

| Area | Contoh skenario | Berkas pengujian |
|---|---|---|
| Login dan role | Password salah ditolak, percobaan login dibatasi, staff tidak bisa masuk fungsi admin | `AccountTest.php`, `GrailFlowTest.php` |
| Produk dan SKU | SKU duplikat dan harga tidak valid ditolak; foto kecil diterima, file bukan gambar ditolak, dan arsip produk dipulihkan | `GrailFlowTest.php`, `ProductMediaArchiveTest.php` |
| Stock In | Penerimaan menambah stok; transaksi gagal tidak menyimpan sebagian | `InventoryIntegrityTest.php`, `TransactionSubmissionTest.php` |
| Penjualan | Penjualan mengurangi stok; stok kurang ditolak; banyak item gagal bersama | `InventoryIntegrityTest.php`, `TransactionAuditTest.php` |
| Checkout | Review harga dan ongkir, validasi alamat, stok terkini, dan token anti-kirim-ulang | `CheckoutFlowTest.php` |
| Pesanan customer | Pembayaran dan pengiriman berurutan; pembatalan sebelum konfirmasi mengembalikan stok satu kali; pesanan orang lain tidak bisa dibatalkan | `OrderWorkflowTest.php` |
| Profil | Kontak tersimpan, email unik, ganti email dan password meminta kata sandi | `AccountTest.php` |

Jalankan ulang dari folder `grail-co`:

```powershell
php artisan test --compact
npm.cmd run build
```

Hasil pengujian terminal terakhir:

```text
Tests:    69 passed (719 assertions)
Duration: 4.93s

vite v7.3.7 building client environment for production...
59 modules transformed
built in 2.33s
```

Screenshot aplikasi tersedia pada [indeks screenshot](screenshots/README.md). Untuk bukti hasil pengujian yang diminta terpisah oleh dosen, ambil screenshot terminal saat dua perintah di atas dijalankan pada perangkat presentasi.
