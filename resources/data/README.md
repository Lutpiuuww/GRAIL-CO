# Data wilayah checkout

`indonesia-regions.json` adalah snapshot lokal 38 provinsi dan 514 kota/kabupaten yang dipakai untuk pilihan checkout dan validasi server. Sumber: [emsifa/api-wilayah-indonesia v2](https://github.com/emsifa/api-wilayah-indonesia), diambil pada 21 Oktober 2026. Sumber tersebut berlisensi MIT dan merangkum data wilayah dari rujukan pemerintah yang disebutkan di dokumentasinya.

Untuk memperbarui snapshot, jalankan `powershell -NoProfile -ExecutionPolicy Bypass -File scripts/update-regions.ps1` dari folder aplikasi, tinjau perubahannya, lalu jalankan tes. Checkout tidak melakukan permintaan ke layanan wilayah eksternal saat digunakan.

Daftar ini hanya memverifikasi pasangan provinsi–kota/kabupaten. Alamat jalan dan kode pos tetap diisi customer dan perlu diperiksa oleh petugas sebelum pengiriman.
