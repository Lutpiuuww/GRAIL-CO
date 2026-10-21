@extends('layouts.app')
@section('title', 'Cart — GRAIL.CO')
@section('content')
<section class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
    <p class="eyebrow">Your selection</p><h1 class="mt-2 text-3xl font-semibold">Acquisition list.</h1>
    @php($blocked = $cart->contains(fn ($row) => $row->product->trashed() || $row->product->stock < $row->quantity))
    <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_300px]">
        <div class="space-y-4">@forelse($cart as $row)
            <article class="glass-panel rounded-2xl p-4">
                <div class="flex gap-4">
                    @if($row->product->image_path)<img src="{{ asset($row->product->image_path) }}" alt="{{ $row->product->name }}" class="size-20 shrink-0 rounded-xl object-cover" loading="lazy" width="80" height="80">
                    @else<div class="grid size-20 shrink-0 place-items-center rounded-xl bg-black/30 font-mono text-sm text-champagne-300" aria-hidden="true">G</div>@endif
                    <div class="min-w-0 flex-1"><h2 class="break-words font-medium">@if($row->product->trashed()){{ $row->product->name }}@else<a href="{{ route('catalog.show', $row->product) }}" class="rounded hover:text-champagne-300">{{ $row->product->name }}</a>@endif</h2><p class="mt-1 font-mono text-xs text-stone-300">{{ $row->product->sku }}</p><p class="mt-2 text-sm text-champagne-300">Rp{{ number_format($row->subtotal, 0, ',', '.') }}</p></div>
                    <form method="POST" action="{{ route('cart.remove', $row->product) }}">@csrf @method('DELETE')<button aria-label="Hapus {{ $row->product->name }}" class="grid size-11 place-items-center rounded-lg text-stone-300 transition-colors hover:bg-red-400/10 hover:text-red-300"><svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M6 18 18 6M6 6l12 12"/></svg></button></form>
                </div>
                @if($row->product->trashed())<p class="mt-4 text-sm text-amber-300">Produk telah diarsipkan. Hapus item ini untuk melanjutkan checkout.</p>
                @elseif($row->product->stock < 1)<p class="mt-4 text-sm text-amber-300">Stok habis. Hapus item ini untuk melanjutkan checkout.</p>
                @else
                    <form method="POST" action="{{ route('cart.update', $row->product) }}" class="mt-4 flex flex-wrap items-end gap-3">
                        @csrf @method('PATCH')
                        <label class="text-sm">Jumlah<input type="number" name="quantity" class="field mt-2 max-w-28" min="1" max="{{ min(100000, $row->product->stock) }}" step="1" value="{{ $row->quantity }}" required></label>
                        <button class="btn-ghost min-h-11 text-sm">Perbarui</button><span class="pb-3 text-xs text-stone-300">Stok: {{ $row->product->stock }}</span>
                    </form>
                    @if($row->quantity > $row->product->stock)<p class="mt-3 text-sm text-amber-300">Jumlah melebihi stok terbaru. Kurangi jumlahnya sebelum checkout.</p>@endif
                @endif
            </article>
        @empty<div class="rounded-2xl border border-dashed border-white/10 py-16 text-center"><p class="text-stone-300">Cart kamu masih kosong.</p><a href="{{ route('catalog') }}" class="btn-ghost mt-5">Explore catalog</a></div>@endforelse</div>
        @if($cart->isNotEmpty())
            <aside class="glass-panel h-fit rounded-2xl p-5"><p class="eyebrow">Summary</p><p class="mt-5 text-sm text-stone-300">{{ $cart->sum('quantity') }} barang dipilih</p><div class="my-5 border-t border-white/10"></div><div class="flex flex-wrap items-end justify-between gap-2"><span>Total</span><strong class="font-mono text-lg text-champagne-300">Rp{{ number_format($total,0,',','.') }}</strong></div>
                @if($blocked)<button class="btn-gold mt-6 w-full" disabled>Lanjut ke checkout</button>@else<a href="{{ route('checkout.create') }}" class="btn-gold mt-6 w-full">Lanjut ke checkout</a>@endif
                <p class="mt-3 text-sm leading-5 {{ $blocked ? 'text-amber-300' : 'text-stone-300' }}">{{ $blocked ? 'Perbarui jumlah atau hapus produk yang tidak tersedia untuk melanjutkan.' : 'Harga dan stok diperiksa kembali saat checkout.' }}</p>
            </aside>
        @endif
    </div>
</section>
@endsection
