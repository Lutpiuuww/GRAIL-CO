@extends('layouts.app')
@section('title', 'Checkout — GRAIL.CO')
@section('content')
@php
    $value = fn ($key, $default = '') => is_scalar(old($key, $details[$key] ?? $default)) ? old($key, $details[$key] ?? $default) : '';
    $delivery = $value('delivery_method', 'standard');
@endphp
<section class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('cart') }}" class="mb-6 inline-flex min-h-11 items-center rounded text-sm text-champagne-300">Kembali ke cart</a>
    @include('checkout.steps', ['step' => 1])
    <p class="eyebrow">Complete your acquisition</p><h1 class="mt-3 text-3xl font-semibold">Lengkapi pesananmu.</h1><p class="mt-3 text-sm leading-6 text-stone-300">Isi data penerima, lalu periksa semua detail sebelum membuat pesanan.</p>
    <div class="mt-8 grid items-start gap-6 lg:grid-cols-[1.25fr_1fr]">
        <form method="POST" action="{{ route('checkout.prepare') }}" class="space-y-6" data-checkout-details>
            @csrf
            <section class="glass-panel space-y-5 rounded-2xl p-5 sm:p-6" aria-labelledby="recipient-title">
                <h2 id="recipient-title" class="text-xl font-semibold">01. Data penerima</h2>
                <label class="block text-sm">Nama penerima<input class="field mt-2" name="recipient_name" autocomplete="name" maxlength="100" value="{{ $value('recipient_name', auth()->user()->name) }}" required>@error('recipient_name')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
                <label class="block text-sm">Nomor telepon<input class="field mt-2" type="tel" name="phone" autocomplete="tel" maxlength="21" placeholder="Contoh: 081234567890" value="{{ $value('phone') }}" required>@error('phone')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
                <p class="break-all text-sm text-stone-300">Email akun: {{ auth()->user()->email }}</p>
            </section>
            <section class="glass-panel rounded-2xl p-5 sm:p-6" aria-labelledby="delivery-title">
                <h2 id="delivery-title" class="text-xl font-semibold">02. Pengiriman atau pengambilan</h2>
                <fieldset class="mt-5 space-y-3"><legend class="sr-only">Pilih metode pengiriman</legend>
                    @foreach(config('checkout.delivery') as $key => $option)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-white/10 p-4 has-[:checked]:border-champagne-400/60 has-[:checked]:bg-champagne-400/5"><input class="mt-1 size-4 accent-champagne-400" type="radio" name="delivery_method" value="{{ $key }}" @checked($delivery === $key) required><span><span class="block text-sm font-medium">{{ $option['label'] }}</span><span class="mt-1 block text-xs leading-5 text-stone-300">{{ $option['hint'] }}</span></span></label>
                    @endforeach
                </fieldset>
                @error('delivery_method')<p class="mt-2 text-sm text-red-300">{{ $message }}</p>@enderror
                <div class="mt-6 space-y-5" data-delivery-address @if($delivery === 'pickup') hidden @endif>
                    <label class="block text-sm">Alamat lengkap<textarea class="field mt-2" name="address" autocomplete="street-address" maxlength="500" rows="3" placeholder="Nama jalan, nomor rumah, RT/RW, dan kecamatan">{{ $value('address') }}</textarea>@error('address')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="block text-sm">Kota / kabupaten<input class="field mt-2" name="city" autocomplete="address-level2" maxlength="100" value="{{ $value('city') }}">@error('city')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
                        <label class="block text-sm">Provinsi<input class="field mt-2" name="province" autocomplete="address-level1" maxlength="100" value="{{ $value('province') }}">@error('province')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
                    </div>
                    <label class="block text-sm">Kode pos<input class="field mt-2" name="postal_code" autocomplete="postal-code" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" value="{{ $value('postal_code') }}">@error('postal_code')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
                </div>
                <noscript><p class="mt-4 text-sm text-stone-300">Alamat hanya wajib diisi untuk metode pengiriman.</p></noscript>
            </section>
            <section class="glass-panel rounded-2xl p-5 sm:p-6" aria-labelledby="payment-title">
                <h2 id="payment-title" class="text-xl font-semibold">03. Metode pembayaran</h2>
                <p class="mt-3 text-sm leading-6 text-amber-300">Pembayaran online belum tersedia. Tim akan mengonfirmasi instruksi pembayaran setelah pesanan dibuat.</p>
                <fieldset class="mt-5 space-y-3"><legend class="sr-only">Pilih metode pembayaran</legend>
                    @foreach(config('checkout.payment') as $key => $option)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-white/10 p-4 has-[:checked]:border-champagne-400/60 has-[:checked]:bg-champagne-400/5"><input class="mt-1 size-4 accent-champagne-400" type="radio" name="payment_method" value="{{ $key }}" @checked($value('payment_method', 'bank_transfer') === $key) required><span><span class="block text-sm font-medium">{{ $option['label'] }}</span><span class="mt-1 block text-xs leading-5 text-stone-300">{{ $option['hint'] }}</span></span></label>
                    @endforeach
                </fieldset>
                @error('payment_method')<p class="mt-2 text-sm text-red-300">{{ $message }}</p>@enderror
                <label class="mt-6 block text-sm">Catatan pesanan <span class="text-stone-300">(opsional)</span><textarea class="field mt-2" name="notes" maxlength="500" rows="3" placeholder="Contoh: patokan alamat atau catatan untuk pesanan">{{ $value('notes') }}</textarea>@error('notes')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
            </section>
            <button class="btn-gold min-h-12 w-full">Lanjut tinjau pesanan</button>
        </form>
        @include('checkout.summary', ['preview' => true])
    </div>
</section>
@endsection
