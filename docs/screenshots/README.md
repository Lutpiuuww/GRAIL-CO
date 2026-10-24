# Bukti tampilan GRAIL.CO

Diambil pada 21 Oktober 2026 dari instalasi lokal dengan akun dan data contoh. Database screenshot terpisah dari database kerja. Bukti aturan server dan concurrency ada di `../UTS_TEST_MATRIX.md`.

| Skenario | Screenshot |
|---|---|
| Login | [01-login.png](01-login.png) |
| Dashboard admin dan stok rendah | [02-admin-dashboard.png](02-admin-dashboard.png) |
| Daftar produk | [03-products.png](03-products.png) |
| Form dan hasil Stock In | [04-stock-in-form.png](04-stock-in-form.png), [05-stock-in-history.png](05-stock-in-history.png) |
| Form dan hasil Sales | [06-sales-form.png](06-sales-form.png), [07-sales-history.png](07-sales-history.png) |
| Nota Sales dan stok tidak cukup | [08-sales-receipt.png](08-sales-receipt.png), [09-insufficient-stock.png](09-insufficient-stock.png) |
| Dashboard staff dan penolakan URL admin | [11-staff-dashboard.png](11-staff-dashboard.png), [12-staff-forbidden.png](12-staff-forbidden.png) |
| Tambahan customer: katalog, detail, checkout, review, sukses | [10-customer-catalog.png](10-customer-catalog.png), [13-customer-product-detail.png](13-customer-product-detail.png), [14-customer-checkout.png](14-customer-checkout.png), [15-customer-review.png](15-customer-review.png), [16-customer-success.png](16-customer-success.png) |
| Customer: nota, riwayat, tracking mobile | [17-customer-order-detail.png](17-customer-order-detail.png), [18-customer-my-orders.png](18-customer-my-orders.png), [19-customer-order-mobile-top.png](19-customer-order-mobile-top.png), [20-customer-tracking-mobile.png](20-customer-tracking-mobile.png) |
| Pembaruan alamat dan produk | [21-checkout-wilayah-mobile.png](21-checkout-wilayah-mobile.png), [22-admin-product-archive.png](22-admin-product-archive.png), [23-admin-product-photo-form.png](23-admin-product-photo-form.png), [24-admin-product-photo-mobile.png](24-admin-product-photo-mobile.png) |

Nomor transaksi dan angka stok pada gambar berasal dari rangkaian pengujian: Stock In 3 unit, Sale 2 unit, percobaan menjual 3 unit berikutnya ditolak ketika stok tinggal 2. Screenshot checkout dan pesanan menggunakan data contoh; tidak ada dana yang berpindah atau pengiriman yang dijalankan. Pembayaran dan pengiriman harus diverifikasi di luar aplikasi. Browser preview cetak belum diverifikasi; screenshot nota menunjukkan tampilan web, bukan hasil PDF/cetak.
