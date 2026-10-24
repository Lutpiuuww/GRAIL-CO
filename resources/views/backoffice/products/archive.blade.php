@extends('layouts.backoffice')
@section('title','Arsip Produk — GRAIL.CO')
@section('content')
<section class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('products.index') }}" class="inline-flex min-h-11 items-center text-sm text-champagne-300 hover:text-champagne-200">← Kembali ke produk aktif</a>
    <div class="mt-4 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><p class="eyebrow">Inventory archive</p><h1 class="mt-2 text-3xl font-semibold">Produk diarsipkan</h1><p class="mt-2 max-w-2xl text-sm leading-6 text-stone-300">Produk di sini tidak muncul di katalog atau transaksi baru. Pulihkan untuk mengaktifkannya kembali; stok dan riwayat tetap tersimpan.</p></div>
    </div>
    <form class="mt-7 flex flex-col gap-3 sm:flex-row" method="GET"><input class="field flex-1" name="q" value="{{ request('q') }}" aria-label="Cari produk diarsipkan" placeholder="Cari nama atau SKU..."><button class="btn-ghost min-h-11">Cari arsip</button></form>
    <div class="mt-6 grid gap-4">
        @forelse($products as $product)
            <article class="glass-panel flex flex-col gap-4 rounded-2xl p-4 sm:flex-row sm:items-center sm:p-5">
                <div class="flex min-w-0 flex-1 items-center gap-4">
                    @if($product->image_path)<img src="{{ asset($product->image_path) }}" alt="" class="size-20 shrink-0 rounded-xl object-cover" loading="lazy">@else<div class="grid size-20 shrink-0 place-items-center rounded-xl bg-white/5 font-mono text-2xl text-champagne-300" aria-hidden="true">G</div>@endif
                    <div class="min-w-0"><h2 class="text-lg font-semibold">{{ $product->name }}</h2><p class="mt-1 font-mono text-xs text-stone-300">{{ $product->sku }} · {{ $product->category->name }}</p><p class="mt-2 text-xs text-stone-300">Stok tersimpan: {{ $product->stock }} · Diarsipkan {{ $product->deleted_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</p></div>
                </div>
                <form method="POST" action="{{ route('products.restore', $product) }}" data-confirm="Pulihkan produk ini ke daftar aktif? Stok lama akan tetap dipakai.">@csrf @method('PATCH')<button class="btn-gold min-h-11 w-full sm:w-auto">Pulihkan produk</button></form>
            </article>
        @empty
            <div class="glass-panel rounded-2xl px-6 py-12 text-center"><h2 class="text-xl font-semibold">Tidak ada produk di arsip.</h2><p class="mt-2 text-sm text-stone-300">Produk yang diarsipkan akan muncul di sini dan dapat dipulihkan kapan saja.</p><a href="{{ route('products.index') }}" class="btn-ghost mt-6 min-h-11">Lihat produk aktif</a></div>
        @endforelse
    </div>
    <div class="mt-6">{{ $products->links() }}</div>
</section>
@endsection
