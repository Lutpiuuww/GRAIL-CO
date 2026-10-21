# Relasi data GRAIL.CO

Delapan tabel bisnis inti didampingi tabel pendukung Laravel, `transaction_submissions` untuk proteksi kiriman ganda, dan `order_events` untuk audit pesanan online.

```mermaid
erDiagram
    USERS ||--o{ PURCHASES : records
    USERS ||--o{ SALES : owns_or_records
    USERS o|--o{ PURCHASES : cancels
    USERS o|--o{ SALES : cancels
    USERS ||--o{ TRANSACTION_SUBMISSIONS : submits
    USERS o|--o{ SALES : records_payment
    USERS ||--o{ ORDER_EVENTS : performs
    SALES ||--o{ ORDER_EVENTS : tracks
    CATEGORIES ||--o{ PRODUCTS : groups
    SUPPLIERS ||--o{ PURCHASES : supplies
    PURCHASES ||--|{ PURCHASE_ITEMS : contains
    SALES ||--|{ SALE_ITEMS : contains
    PRODUCTS ||--o{ PURCHASE_ITEMS : received
    PRODUCTS ||--o{ SALE_ITEMS : sold
    USERS {
        bigint id PK
        string email UK
        string name
        string password
        enum role
        boolean is_active
    }
    CATEGORIES {
        bigint id PK
        string name UK
    }
    SUPPLIERS {
        bigint id PK
        string name
        string phone
        string email
    }
    PRODUCTS {
        bigint id PK
        bigint category_id FK
        string sku UK
        string name
        decimal purchase_price
        decimal selling_price
        int stock
        int minimum_stock
        timestamp deleted_at
    }
    PURCHASES {
        bigint id PK
        bigint user_id FK
        bigint supplier_id FK
        string purchase_number UK
        decimal total
        enum status
        timestamp purchased_at
        bigint cancelled_by FK
        timestamp cancelled_at
        text cancellation_reason
    }
    PURCHASE_ITEMS {
        bigint id PK
        bigint purchase_id FK
        bigint product_id FK
        int quantity
        decimal unit_cost
        decimal subtotal
    }
    SALES {
        bigint id PK
        bigint user_id FK
        string sale_number UK
        decimal total
        enum status
        timestamp sold_at
        bigint cancelled_by FK
        timestamp cancelled_at
        text cancellation_reason
        uuid checkout_token UK
        json delivery_details
        string delivery_method
        decimal shipping_cost
        string payment_method
        string payment_status
        string fulfillment_status
        int order_version
        string payment_reference
        bigint paid_by FK
        timestamp paid_at
        timestamp refunded_at
        string carrier
        string tracking_number
        text pickup_location
        timestamp pickup_at
        timestamp shipped_at
        timestamp ready_at
        timestamp completed_at
    }
    ORDER_EVENTS {
        bigint id PK
        bigint sale_id FK
        bigint user_id FK
        string type
        string from_status
        string to_status
        text note
        json details
        timestamp created_at
    }
    SALE_ITEMS {
        bigint id PK
        bigint sale_id FK
        bigint product_id FK
        int quantity
        decimal unit_price
        decimal subtotal
    }
    TRANSACTION_SUBMISSIONS {
        bigint id PK
        bigint user_id FK
        string scope
        uuid token
        timestamp created_at
    }
```

`cancelled_by` dan kolom audit boleh kosong untuk transaksi lama. Produk diarsipkan melalui soft delete, sedangkan akun dinonaktifkan melalui `is_active`; detail transaksi tetap mengakses produk terarsip. Kombinasi `user_id`, `scope`, dan `token` unik pada tabel submission. Harga satuan dan subtotal disimpan pada detail saat transaksi dibuat; nota tidak mengambil harga jual terbaru.

Pada Sales, `user_id` adalah pemilik pesanan untuk checkout online atau petugas pencatat untuk penjualan kasir. Database saat ini belum menyimpan identitas pembeli terpisah pada transaksi kasir.

Checkout online menyimpan `checkout_token` unik untuk mengarahkan kiriman ulang ke nota yang sama. `delivery_details` menyimpan snapshot nama/telepon/email penerima, alamat bila dikirim, dan catatan. `total` pada checkout baru mencakup subtotal item ditambah `shipping_cost`. Metode pembayaran simulasi dicatat dengan `payment_status=pending`; kolom baru nullable pada transaksi lama/POS agar data historis tidak diubah atau dianggap sudah dibayar secara otomatis.

`fulfillment_status` terpisah dari status pencatatan transaksi. Pesanan online lama diisi pending/cancelled sesuai status transaksinya; POS tetap nullable. `order_version` berubah pada setiap aksi untuk menolak pembaruan dari halaman lama. `order_events` ditulis dalam transaksi yang sama dengan pembayaran/progres/void dan tidak memiliki endpoint edit/hapus. Waktu disimpan UTC, jadwal dan timeline ditampilkan WIB.
