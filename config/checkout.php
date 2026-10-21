<?php

return [
    'delivery' => [
        'standard' => ['label' => 'Pengiriman reguler', 'fee_cents' => 2500000, 'hint' => 'Biaya pengiriman Rp25.000.'],
        'express' => ['label' => 'Pengiriman express', 'fee_cents' => 4500000, 'hint' => 'Biaya pengiriman Rp45.000.'],
        'pickup' => ['label' => 'Ambil di toko', 'fee_cents' => 0, 'hint' => 'Tanpa ongkir. Lokasi dan jadwal dikonfirmasi terlebih dahulu.'],
    ],
    'payment' => [
        'bank_transfer' => ['label' => 'Transfer bank', 'hint' => 'Instruksi pembayaran akan dikonfirmasi oleh tim melalui kontak penerima.'],
        'cod' => ['label' => 'Bayar saat diterima / diambil', 'hint' => 'Pembayaran dicatat setelah barang diterima atau diambil.'],
    ],
];
