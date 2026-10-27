@extends('layouts.app')
@section('title', 'Pesanan tercatat — GRAIL.CO')
@section('content')
<section class="mx-auto max-w-4xl px-4 py-10 sm:px-6">
    @include('checkout.steps', ['step' => 3])
    <article class="glass-panel rounded-3xl p-5 sm:p-8">
        @if($sale->status === 'cancelled')
            <span class="grid size-14 place-items-center rounded-full border border-red-400/30 bg-red-400/10 text-red-300"><svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" stroke-width="2" stroke-linecap="round"/></svg></span>
        @else
            <span class="grid size-14 place-items-center rounded-full border border-emerald-400/30 bg-emerald-400/10 text-emerald-300"><svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="m5 12 4 4L19 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
        @endif
        <p class="eyebrow mt-6">Your acquisition is recorded</p><h1 class="mt-3 text-3xl font-semibold">{{ $sale->status === 'cancelled' ? 'Pesanan ini telah dibatalkan.' : 'Pesananmu sudah tercatat.' }}</h1>
        <p class="mt-3 text-sm leading-6 text-stone-300">{{ $sale->status === 'cancelled' ? 'Lihat nota untuk rincian pembatalan.' : ($sale->payment_status === 'pending' ? 'Simpan nomor pesanan di bawah. Pembayaran belum dikonfirmasi. Perkembangan pesanan tersedia di bagian berikut.' : 'Simpan nomor pesanan di bawah. Perkembangan pesanan dan status pembayaran tersedia di bagian berikut.') }}</p>
        <p class="mt-5 break-all font-mono text-sm text-champagne-300">{{ $sale->sale_number }}</p>
        @include('orders.cancel-form')
        <dl class="mt-6 space-y-3 border-y border-white/10 py-5 text-sm"><div class="flex justify-between gap-3"><dt>Subtotal barang</dt><dd>Rp{{ number_format($sale->items->sum('subtotal'), 0, ',', '.') }}</dd></div><div class="flex justify-between gap-3"><dt>Biaya pengiriman</dt><dd>Rp{{ number_format($sale->shipping_cost, 0, ',', '.') }}</dd></div><div class="flex flex-wrap justify-between gap-3 text-base font-semibold"><dt>Total pesanan</dt><dd class="text-champagne-300">Rp{{ number_format($sale->total, 0, ',', '.') }}</dd></div></dl>
        @include('orders.delivery-details', ['sale' => $sale])
        <div class="mt-6 flex flex-col gap-3 sm:flex-row"><a href="{{ route('orders.show', $sale) }}" class="btn-gold">Lihat detail dan nota</a><a href="{{ route('orders') }}" class="btn-ghost">Pesanan saya</a><a href="{{ route('catalog') }}" class="btn-ghost">Lanjut jelajahi</a></div>
    </article>
</section>
@endsection
