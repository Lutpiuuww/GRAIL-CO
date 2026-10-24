@extends('layouts.backoffice')
@section('title','Products — GRAIL.CO')
@section('content')
<section class="px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div><p class="eyebrow">Inventory</p><h1 class="mt-2 text-3xl font-semibold">Products</h1><p class="mt-2 text-sm text-stone-300">Stock, pricing, dan detail curated collection.</p></div>
        @if(auth()->user()->role === 'admin')
            <div class="flex flex-wrap gap-3"><a href="{{ route('products.archive') }}" class="btn-ghost min-h-11">Lihat arsip</a><a href="{{ route('products.create') }}" class="btn-gold min-h-11">Add product</a></div>
        @else<span class="rounded-full border border-white/10 px-3 py-2 text-xs text-stone-300">Read-only access</span>@endif
    </div>
    <div class="glass-panel mt-8 overflow-hidden rounded-2xl">
        <form class="grid gap-3 border-b border-white/10 p-4 md:grid-cols-[1fr_260px_180px_auto]">
            <input name="q" value="{{ request('q') }}" class="field" aria-label="Cari produk" placeholder="Search product or SKU...">
            <select name="category" class="field cursor-pointer" aria-label="Filter kategori"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>@endforeach</select>
            <select name="status" class="field cursor-pointer" aria-label="Filter status"><option value="">All stock</option><option value="low" @selected(request('status') === 'low')>Low stock</option></select>
            <button class="btn-ghost min-h-11">Filter</button>
        </form>
        <div class="overflow-x-auto"><table class="w-full min-w-[960px] text-left text-sm">
            <thead class="bg-black/20 font-mono text-[10px] uppercase tracking-wider text-stone-400"><tr><th class="px-5 py-3">Product</th><th class="px-5 py-3">Category</th><th class="px-5 py-3">Buy</th><th class="px-5 py-3">Sell</th><th class="px-5 py-3">Stock</th><th class="px-5 py-3">Status</th>@if(auth()->user()->role === 'admin')<th class="px-5 py-3 text-right">Actions</th>@endif</tr></thead>
            <tbody class="divide-y divide-white/5">
                @forelse($products as $product)
                    <tr class="hover:bg-white/[.03]">
                        <td class="px-5 py-4"><div class="flex items-center gap-3">@if($product->image_path)<img src="{{ asset($product->image_path) }}" alt="" class="size-12 shrink-0 rounded-lg object-cover" loading="lazy">@else<div class="grid size-12 shrink-0 place-items-center rounded-lg bg-white/5 font-mono text-champagne-300" aria-hidden="true">G</div>@endif<div><p class="font-medium">{{ $product->name }}</p><p class="mt-1 font-mono text-[10px] text-stone-400">{{ $product->sku }}</p></div></div></td>
                        <td class="px-5 py-4 text-stone-300">{{ $product->category->name }}</td>
                        <td class="px-5 py-4 font-mono text-xs">Rp{{ number_format($product->purchase_price, 0, ',', '.') }}</td>
                        <td class="px-5 py-4 font-mono text-xs text-champagne-300">Rp{{ number_format($product->selling_price, 0, ',', '.') }}</td>
                        <td class="px-5 py-4 font-mono">{{ $product->stock }}</td>
                        <td class="px-5 py-4"><span class="rounded-full px-2 py-1 text-xs {{ $product->is_low_stock ? 'bg-amber-400/10 text-amber-300' : 'bg-emerald-400/10 text-emerald-300' }}">{{ $product->is_low_stock ? 'Low stock' : 'Healthy' }}</span></td>
                        @if(auth()->user()->role === 'admin')<td class="px-5 py-4"><div class="flex justify-end gap-2"><a class="btn-ghost min-h-11 !px-3 text-xs" href="{{ route('products.edit', $product) }}">Edit</a><form method="POST" action="{{ route('products.destroy', $product) }}" data-confirm="Arsipkan produk ini? Riwayat transaksi tidak akan terhapus.">@csrf @method('DELETE')<button class="min-h-11 rounded-lg px-3 text-xs text-red-300 hover:bg-red-400/10">Archive</button></form></div></td>@endif
                    </tr>
                @empty
                    <tr><td colspan="{{ auth()->user()->role === 'admin' ? 7 : 6 }}" class="px-5 py-12 text-center text-sm text-stone-300">Tidak ada produk yang sesuai filter.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </div>
    <div class="mt-6">{{ $products->links() }}</div>
</section>
@endsection
