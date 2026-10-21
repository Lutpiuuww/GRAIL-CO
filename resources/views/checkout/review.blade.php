@extends('layouts.app')
@section('title', 'Tinjau pesanan — GRAIL.CO')
@section('content')
<section class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('checkout.create') }}" class="mb-6 inline-flex min-h-11 items-center rounded text-sm text-champagne-300">Ubah data checkout</a>
    @include('checkout.steps', ['step' => 2])
    <p class="eyebrow">One last look</p><h1 class="mt-3 text-3xl font-semibold">Tinjau pesananmu.</h1><p class="mt-3 text-sm leading-6 text-stone-300">Periksa penerima, metode, dan total. Pesanan belum dibuat sampai kamu mengonfirmasi.</p>
    @if($changed)<p role="status" class="mt-5 rounded-xl border border-amber-400/30 bg-amber-400/5 p-4 text-sm text-amber-300">Harga atau ongkir diperbarui. Periksa total terbaru di bawah.</p>@endif
    <div class="mt-8 grid items-start gap-6 lg:grid-cols-[1.25fr_1fr]">
        <div class="space-y-6">
            <section class="glass-panel rounded-2xl p-5 sm:p-6">
                <h2 class="text-xl font-semibold">Penerima dan tujuan</h2><p class="mt-5 font-medium">{{ $draft['details']['recipient_name'] }}</p><p class="mt-2 text-sm text-stone-300">{{ $draft['details']['phone'] }}</p><p class="mt-2 break-all text-sm text-stone-300">{{ $draft['details']['email'] }}</p>
                <p class="mt-5 text-sm font-medium text-champagne-300">{{ config('checkout.delivery.'.$draft['details']['delivery_method'].'.label') }}</p>
                @if($draft['details']['delivery_method'] !== 'pickup')<p class="mt-3 whitespace-pre-line break-words text-sm leading-6 text-stone-300">{{ $draft['details']['address'] }}</p><p class="mt-2 text-sm text-stone-300">{{ $draft['details']['city'] }}, {{ $draft['details']['province'] }} {{ $draft['details']['postal_code'] }}</p>@else<p class="mt-3 text-sm leading-6 text-stone-300">Lokasi dan jadwal pengambilan perlu dikonfirmasi. Pesanan ini belum siap diambil.</p>@endif
            </section>
            <section class="glass-panel rounded-2xl p-5 sm:p-6"><h2 class="text-xl font-semibold">Pembayaran</h2><p class="mt-4 text-sm text-champagne-300">{{ config('checkout.payment.'.$draft['details']['payment_method'].'.label') }}</p><p class="mt-3 text-sm leading-6 text-stone-300">{{ config('checkout.payment.'.$draft['details']['payment_method'].'.hint') }}</p>
                @if(filled($draft['details']['notes'] ?? null))<h3 class="mt-6 font-medium">Catatan pesanan</h3><p class="mt-2 whitespace-pre-line break-words text-sm text-stone-300">{{ $draft['details']['notes'] }}</p>@endif
            </section>

        </div>
        @include('checkout.summary', ['preview' => false])
        <form method="POST" action="{{ route('checkout') }}" class="glass-panel rounded-2xl p-5 sm:p-6 lg:col-span-2">
            @csrf<input type="hidden" name="submission_token" value="{{ $draft['token'] }}">
            <p class="text-sm leading-6 text-stone-300">Setelah dikonfirmasi, pesanan akan tercatat dan stok berkurang. Pembayaran online belum tersedia; status pesanan diperbarui oleh tim.</p>
            <button class="btn-gold mt-5 min-h-12 w-full">Buat pesanan · Rp{{ number_format($quote['total_cents'] / 100, 0, ',', '.') }}</button>
            <a href="{{ route('cart') }}" class="mt-4 flex min-h-11 items-center justify-center rounded text-sm text-champagne-300">Ubah barang di cart</a>
        </form>
    </div>
</section>
@endsection
