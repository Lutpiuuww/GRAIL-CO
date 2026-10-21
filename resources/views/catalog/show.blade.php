@extends('layouts.app')
@section('title', $product->name.' — GRAIL.CO')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
    <nav aria-label="Breadcrumb" class="mb-6 flex flex-wrap items-center gap-2 text-sm text-stone-300">
        <a href="{{ route('catalog') }}" class="rounded text-champagne-300 hover:underline">Catalog</a><span aria-hidden="true">/</span>
        <a href="{{ route('catalog', ['category' => $product->category_id]) }}" class="rounded hover:text-champagne-300">{{ $product->category->name }}</a><span aria-hidden="true">/</span><span aria-current="page">{{ $product->sku }}</span>
    </nav>
    <div class="grid items-start gap-7 lg:grid-cols-2 lg:gap-12">
        <div class="overflow-hidden rounded-3xl border border-white/10 bg-obsidian-800">
            @if($product->image_path)
                <img src="{{ asset($product->image_path) }}" alt="{{ $product->name }}" class="aspect-[4/5] w-full object-cover" width="800" height="1000" fetchpriority="high" decoding="async">
            @else
                <div class="grid aspect-[4/5] place-items-center bg-[radial-gradient(circle,rgba(216,184,103,.15),transparent_65%)]"><span class="font-mono text-4xl text-champagne-300" aria-label="Foto produk belum tersedia">GRAIL.CO</span></div>
            @endif
        </div>
        <div class="min-w-0 lg:py-4">
            <p class="eyebrow">{{ $product->category->name }}</p>
            <h1 class="mt-4 break-words text-3xl font-semibold leading-tight sm:text-4xl">{{ $product->name }}</h1>
            <p class="mt-3 font-mono text-xs text-stone-300">{{ $product->sku }}</p>
            <p class="mt-6 font-mono text-2xl text-champagne-300 sm:text-3xl">Rp{{ number_format($product->selling_price, 0, ',', '.') }}</p>
            <p class="mt-4 inline-flex rounded-full px-3 py-2 text-sm {{ $product->stock > 0 ? ($product->is_low_stock ? 'bg-amber-400/10 text-amber-300' : 'bg-emerald-400/10 text-emerald-300') : 'bg-red-400/10 text-red-300' }}">{{ $product->stock > 0 ? ($product->is_low_stock ? 'Stok terbatas' : 'Tersedia') : 'Stok habis' }}@if($product->stock > 0) · {{ $product->stock }} unit @endif</p>
            <section class="mt-7 border-t border-white/10 pt-6" aria-labelledby="description-title">
                <h2 id="description-title" class="text-lg font-semibold">Tentang produk</h2>
                <p class="mt-3 whitespace-pre-line break-words text-sm leading-7 text-stone-300">{{ filled($product->description) ? $product->description : 'Deskripsi produk belum tersedia.' }}</p>
            </section>
            <dl class="mt-6 grid grid-cols-2 gap-4 rounded-2xl border border-white/10 bg-obsidian-800/70 p-5 text-sm">
                <div><dt class="text-stone-300">Kategori</dt><dd class="mt-1 font-medium">{{ $product->category->name }}</dd></div>
                <div><dt class="text-stone-300">Kode produk</dt><dd class="mt-1 break-all font-mono">{{ $product->sku }}</dd></div>
            </dl>
            <div class="glass-panel mt-7 rounded-2xl p-5">
                @if($product->stock > 0)
                    @auth
                        <form method="POST" action="{{ route('cart.add', $product) }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                            @csrf
                            <label class="text-sm sm:w-28">Jumlah<input class="field mt-2" type="number" name="quantity" min="1" max="{{ min($product->stock, 100000) }}" step="1" value="{{ is_scalar(old('quantity', 1)) ? old('quantity', 1) : 1 }}" required aria-describedby="quantity-hint"></label>
                            <button class="btn-gold min-h-12 flex-1">Tambahkan ke cart</button>
                        </form>
                        <p id="quantity-hint" class="mt-3 text-sm text-stone-300">Stok dan harga diperiksa kembali saat checkout.</p>
                        @error('quantity')<p class="mt-3 text-sm text-red-300">{{ $message }}</p>@enderror
                        <a href="{{ route('cart') }}" class="mt-4 inline-flex min-h-11 items-center rounded text-sm text-champagne-300 hover:underline">Lihat cart</a>
                    @else
                        <a href="{{ route('login') }}" class="btn-gold min-h-12 w-full">Masuk untuk membeli</a>
                        <p class="mt-3 text-sm text-stone-300">Masuk ke akun untuk menambahkan produk ke cart.</p>
                    @endauth
                @else
                    <button class="btn-gold min-h-12 w-full" disabled>Stok habis</button>
                    <p class="mt-3 text-sm text-stone-300">Produk ini belum bisa ditambahkan ke cart. Jelajahi koleksi lain yang tersedia.</p>
                @endif
            </div>
        </div>
    </div>
    @if($related->isNotEmpty())
        <section class="mt-14 border-t border-white/10 pt-8" aria-labelledby="related-title">
            <p class="eyebrow">Explore the archive</p><h2 id="related-title" class="mt-2 text-2xl font-semibold">Dalam koleksi yang sama</h2>
            <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">@foreach($related as $item)
                <a href="{{ route('catalog.show', $item) }}" class="group overflow-hidden rounded-2xl border border-white/10 bg-obsidian-800 transition-colors hover:border-champagne-400/50 motion-reduce:transition-none">
                    @if($item->image_path)<img src="{{ asset($item->image_path) }}" alt="{{ $item->name }}" class="aspect-[4/5] w-full object-cover" loading="lazy" width="400" height="500">@else<div class="grid aspect-[4/5] place-items-center font-mono text-champagne-300" aria-hidden="true">G</div>@endif
                    <div class="p-4"><h3 class="text-sm font-medium group-hover:text-champagne-300">{{ $item->name }}</h3><p class="mt-3 font-mono text-xs text-champagne-300">Rp{{ number_format($item->selling_price, 0, ',', '.') }}</p></div>
                </a>
            @endforeach</div>
        </section>
    @endif
</section>
@endsection
