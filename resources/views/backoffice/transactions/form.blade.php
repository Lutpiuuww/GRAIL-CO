@extends('layouts.backoffice')
@php($isPurchase = $type === 'purchase')
@section('title', ($isPurchase ? 'New Stock In' : 'New POS Sale').' — GRAIL.CO')
@section('content')
<section class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route($isPurchase ? 'purchases.index' : 'sales.index') }}" class="text-sm text-champagne-300">Kembali ke transaksi</a>
    <div class="glass-panel mt-5 rounded-2xl p-5 sm:p-7">
        <p class="eyebrow">{{ $isPurchase ? 'Inbound inventory' : 'Point of sale' }}</p><h1 class="mt-2 text-2xl font-semibold">{{ $isPurchase ? 'Catat penerimaan barang' : 'Catat penjualan kasir' }}</h1>
        <p class="mt-2 text-sm text-stone-300">Pilih produk dan jumlahnya. Harga mengikuti harga yang berlaku saat disimpan.</p>
        <form class="mt-7" method="POST" action="{{ route($isPurchase ? 'purchases.store' : 'sales.store') }}" data-transaction-form>
            @csrf
            <input type="hidden" name="submission_token" value="{{ old('submission_token', (string) Str::uuid()) }}">
            @if($isPurchase)<label class="block text-sm">Supplier<select class="field mt-2" name="supplier_id" required><option value="">Pilih supplier</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>@endforeach</select></label>@endif
            @php($rows = is_array(old('items')) ? array_slice(old('items'), 0, 100, true) : [['product_id' => '', 'quantity' => 1]])
            <div class="mt-6 space-y-4" data-item-list>
                @foreach($rows as $index => $row)
                    @php($row = is_array($row) ? $row : [])
                    <div class="grid items-end gap-3 rounded-xl border border-white/10 p-4 sm:grid-cols-[1fr_100px_auto]" data-item-row>
                        <label class="min-w-0 text-sm">Produk<select class="field mt-2" name="items[{{ $index }}][product_id]" data-product required><option value="">Pilih produk</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected(($row['product_id'] ?? '') == $product->id)>{{ $product->sku }} — {{ $product->name }} (stok {{ $product->stock }})</option>@endforeach</select></label>
                        <label class="text-sm">Jumlah<input class="field mt-2" type="number" min="1" max="100000" step="1" name="items[{{ $index }}][quantity]" data-quantity value="{{ is_scalar($row['quantity'] ?? '') ? ($row['quantity'] ?? '') : '' }}" required></label>
                        <button type="button" class="btn-ghost min-h-11 text-sm" data-remove-item>Hapus item</button>
                        @error("items.$index.product_id")<p class="text-sm text-red-300 sm:col-span-3">{{ $message }}</p>@enderror
                        @error("items.$index.quantity")<p class="text-sm text-red-300 sm:col-span-3">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
            <template data-item-template><div class="grid items-end gap-3 rounded-xl border border-white/10 p-4 sm:grid-cols-[1fr_100px_auto]" data-item-row>
                <label class="min-w-0 text-sm">Produk<select class="field mt-2" data-product required><option value="">Pilih produk</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->sku }} — {{ $product->name }} (stok {{ $product->stock }})</option>@endforeach</select></label>
                <label class="text-sm">Jumlah<input class="field mt-2" type="number" min="1" max="100000" step="1" data-quantity value="1" required></label>
                <button type="button" class="btn-ghost min-h-11 text-sm" data-remove-item>Hapus item</button>
            </div></template>
            <div class="mt-4 flex flex-wrap items-center gap-3"><button type="button" class="btn-ghost" data-add-item>Tambah item</button><p class="text-sm text-stone-300" data-item-status role="status">Maksimal 100 item. Pilih setiap produk satu kali.</p></div>
            <div class="mt-7 flex flex-wrap gap-3"><button class="btn-gold" @disabled($products->isEmpty() || ($isPurchase && $suppliers->isEmpty()))>{{ $isPurchase ? 'Simpan Stock In' : 'Simpan penjualan' }}</button><a class="btn-ghost" href="{{ route($isPurchase ? 'purchases.index' : 'sales.index') }}">Batal</a></div>
            @if($products->isEmpty())<p class="mt-4 text-sm text-amber-300">Belum ada produk yang tersedia untuk transaksi ini.</p>@endif
            <noscript><p class="mt-4 text-sm text-amber-300">Aktifkan JavaScript untuk menambah atau menghapus baris item.</p></noscript>
        </form>
    </div>
</section>
@endsection
