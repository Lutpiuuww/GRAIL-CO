# Panduan demo UTS dan tanya jawab kode

Urutan ini mengikuti fokus modul dosen: business rule, konsistensi stok, role, dan kemampuan menjelaskan kode. Gunakan database demo yang boleh diubah; jangan melakukan skenario uji destruktif pada data kerja yang ingin disimpan.

## Skenario demo singkat

1. Login sebagai admin. Perlihatkan dashboard, stok rendah, kategori, produk, supplier, dan user. Jelaskan bahwa admin memiliki hak mengelola master dan void.
2. Login sebagai staff. Buka Stock In, pilih supplier, tambahkan satu produk dan kuantitas. Setelah simpan, tunjukkan stok bertambah dan nota pembelian.
3. Dari Sales, jual produk dengan jumlah yang tersedia. Tunjukkan total dihitung server dan stok turun setelah transaksi tersimpan.
4. Coba jumlah yang melebihi stok. Tunjukkan pesan validasi dan stok tetap. Untuk uji rollback multi-item, gunakan data demo/test, lalu jelaskan bahwa kegagalan satu item membatalkan seluruh transaksi.
5. Tunjukkan bahwa staff tidak bisa membuka URL admin untuk Users atau void. Login admin dan void sale yang memenuhi syarat, lalu tunjukkan stok kembali satu kali.
6. Sebagai tambahan sesuai arahan dosen, login customer dan perlihatkan detail produk, checkout simulasi, serta My Orders. Bedakan alur tambahan ini dari penjualan kasir inti modul.

## Letak jawaban di kode

| Pertanyaan dosen | File yang ditunjukkan | Penjelasan ringkas |
|---|---|---|
| Mengapa Sale memiliki banyak SaleItem? | `app/Models/Sale.php`, `app/Models/SaleItem.php`, `database/migrations/2026_10_19_000001_create_grail_core_tables.php` | Satu transaksi memuat beberapa baris produk; `sale_items.sale_id` mengacu ke header. |
| Di mana stok dicegah negatif? | `app/Http/Controllers/SaleController.php`, `app/Http/Requests/TransactionRequest.php` | Jumlah harus positif; produk dikunci dan stok dicek lagi sebelum dikurangi. |
| Mengapa total dihitung di server? | `app/Http/Controllers/SaleController.php` | Harga diambil dari Product di database; subtotal item dijumlahkan ke total. Input harga browser tidak dipercaya. |
| Apa fungsi DB transaction? | `app/Services/TransactionSubmission.php`, `app/Http/Controllers/SaleController.php` | Header, item, perubahan stok, dan token kiriman ganda disimpan atomik; pengecualian membatalkan semuanya. |
| Bagaimana staff dibatasi? | `routes/web.php`, `app/Http/Middleware/EnsureRole.php`, `app/Http/Requests/TransactionRequest.php` | Middleware melindungi route admin di server, bukan sekadar menyembunyikan tombol. |
| Bagaimana dua transaksi stok bersamaan ditangani? | `SaleController.php`, `tests/Support/mysql-concurrency.php` | `lockForUpdate()` mengunci baris produk saat stok terakhir diperiksa; probe MariaDB membuktikan hanya satu checkout terakhir berhasil. |
| Di mana SKU unik? | `app/Http/Controllers/ProductController.php`, migration tabel products | Validasi memberi pesan yang jelas; unique constraint database menjaga integritas saat request bersamaan. |
| Bagaimana minimum stock diubah? | `app/Models/Product.php`, form produk dan dashboard | Edit ambang produk, lalu lihat daftar stok rendah berubah berdasarkan perbandingan stok dengan minimum. |

Hasil pengujian yang tercatat pada 21 Oktober 2026: 55 test/566 assertions lulus di SQLite dan MariaDB QA. Tunjukkan perintah `php artisan test` dan matriks di `docs/UTS_TEST_MATRIX.md`; jangan menyatakan screenshot atau clone bersih sudah diverifikasi sebelum bukti tersebut tersedia.
