@extends('layouts.app')
@section('title', 'Curated Vault — GRAIL.CO')
@section('content')
<section class="mx-auto max-w-7xl px-4 pb-10 pt-10 sm:px-6 lg:px-8 lg:pt-16">
    <div class="grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end"><div><p class="eyebrow">Drop 10.26 / Private archive</p><h1 class="mt-4 max-w-4xl text-4xl font-light leading-tight sm:text-6xl lg:text-7xl">Objects of desire.<br><span class="font-semibold text-champagne-300">Curated to endure.</span></h1></div><p class="max-w-sm text-sm leading-6 text-stone-400 lg:pb-2">Archive streetwear dan mechanical timepieces dalam satu curated vault. Jumlah terbatas, provenance terjaga.</p></div>
    <form class="glass-panel mt-10 grid gap-3 rounded-2xl p-3 sm:grid-cols-[1fr_280px_auto]" role="search"><label class="sr-only" for="q">Cari koleksi</label><input class="field" id="q" name="q" value="{{ request('q') }}" placeholder="Cari nama atau SKU..."><label class="sr-only" for="category">Kategori</label><select class="field" id="category" name="category"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>@endforeach</select><button class="btn-gold">Explore vault</button></form>

    <div class="mt-8 flex items-center justify-between"><div><p class="eyebrow">Available now</p><h2 class="mt-2 text-2xl font-semibold">The current selection</h2></div><span class="font-mono text-xs text-stone-500">{{ str_pad($products->total(), 2, '0', STR_PAD_LEFT) }} OBJECTS</span></div>
    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
        @forelse($products as $product)
        <article class="group overflow-hidden rounded-2xl border border-white/10 bg-obsidian-800 transition-colors duration-200 hover:border-champagne-400/40 motion-reduce:transition-none">
            <a href="{{ route('catalog.show', $product) }}" aria-label="Lihat detail {{ $product->name }}" class="block relative aspect-[4/5] overflow-hidden bg-[radial-gradient(circle_at_55%_35%,rgba(216,184,103,.22),transparent_22%),linear-gradient(145deg,#292524,#0b0a09)]">
                @if($product->image_path)
                    <img src="{{ asset($product->image_path) }}" alt="{{ $product->name }}" class="h-full w-full object-cover transition-[filter] duration-300 group-hover:brightness-110 motion-reduce:transition-none" loading="lazy" decoding="async">
                @else
                    <div class="absolute inset-0 grid place-items-center"><div class="grid size-24 place-items-center rounded-full border border-white/10 bg-black/20 font-mono text-xl text-champagne-300 sm:size-32">{{ Str::afterLast($product->sku, '-') }}</div></div>
                @endif
                <span class="absolute left-3 top-3 rounded-full border border-white/10 bg-black/40 px-2 py-1 font-mono text-[9px] text-stone-300 backdrop-blur">{{ $product->sku }}</span>
                @if($product->is_low_stock)<span class="absolute right-3 top-3 rounded-full bg-amber-300 px-2 py-1 font-mono text-[9px] font-semibold text-stone-950">LAST {{ $product->stock }}</span>@endif
            </a>
            <div class="p-3 sm:p-4"><p class="truncate text-[10px] uppercase tracking-wider text-stone-500">{{ $product->category->name }}</p><h3 class="mt-2 min-h-10 text-sm font-medium leading-5 sm:text-base"><a class="rounded hover:text-champagne-300" href="{{ route('catalog.show', $product) }}">{{ $product->name }}</a></h3><div class="mt-4 flex items-end justify-between gap-2"><p class="font-mono text-xs text-champagne-300 sm:text-sm">Rp{{ number_format($product->selling_price, 0, ',', '.') }}</p>
            @auth<form method="POST" action="{{ route('cart.add', $product) }}">@csrf<button aria-label="Tambahkan {{ $product->name }} ke cart" class="grid size-9 place-items-center rounded-full bg-champagne-400 text-obsidian-950 transition-colors hover:bg-champagne-300"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg></button></form>@else<a href="{{ route('login') }}" aria-label="Masuk untuk membeli" class="grid size-9 place-items-center rounded-full border border-white/10 text-stone-300 hover:border-champagne-400/50 hover:text-champagne-300"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg></a>@endauth</div><a href="{{ route('catalog.show', $product) }}" class="mt-3 flex min-h-11 items-center justify-center rounded-xl border border-white/10 px-3 text-xs text-stone-200 transition-colors hover:border-champagne-400/40 hover:text-champagne-300" aria-label="Lihat detail {{ $product->name }}">Lihat detail</a></div>
        </article>
        @empty<div class="col-span-full rounded-2xl border border-dashed border-white/10 py-16 text-center text-stone-400">Tidak ada koleksi yang cocok.</div>@endforelse
    </div>
    @if($products->hasPages())<div class="mt-8">{{ $products->links() }}</div>@endif
</section>
@endsection
