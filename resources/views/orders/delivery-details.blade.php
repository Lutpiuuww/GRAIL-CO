@if($sale->checkout_token)
<section class="mt-6 rounded-2xl border border-white/10 p-5" aria-label="Data checkout">
    <h2 class="text-lg font-semibold">Penerima dan metode pesanan</h2>
    <div class="mt-4 grid gap-6 text-sm sm:grid-cols-2">
        <div><p class="font-medium">{{ $sale->delivery_details['recipient_name'] }}</p><p class="mt-2 text-stone-300">{{ $sale->delivery_details['phone'] }}</p><p class="mt-2 break-all text-stone-300">{{ $sale->delivery_details['email'] }}</p>
            @if($sale->delivery_method !== 'pickup')<p class="mt-3 whitespace-pre-line break-words leading-6 text-stone-300">{{ $sale->delivery_details['address'] }}</p><p class="mt-2 text-stone-300">{{ $sale->delivery_details['city'] }}, {{ $sale->delivery_details['province'] }} {{ $sale->delivery_details['postal_code'] }}</p>@endif
        </div>
        <div><p class="font-medium">{{ config('checkout.delivery.'.$sale->delivery_method.'.label') }}</p><p class="mt-3 text-stone-300">{{ config('checkout.payment.'.$sale->payment_method.'.label') }}</p><p class="mt-3 text-amber-300">{{ $sale->order_status_label }} · {{ $sale->payment_status_label }}</p><p class="mt-3 text-xs leading-6 text-stone-300">Pembayaran dikonfirmasi secara manual oleh tim GRAIL.CO.</p></div>
    </div>
    @if($sale->carrier && $sale->tracking_number)<dl class="mt-5 grid gap-3 rounded-xl bg-white/5 p-4 text-sm sm:grid-cols-2"><div><dt class="text-stone-300">Kurir</dt><dd class="mt-1 break-words font-medium">{{ $sale->carrier }}</dd></div><div><dt class="text-stone-300">Nomor resi</dt><dd class="mt-1 break-all font-mono">{{ $sale->tracking_number }}</dd></div></dl>@endif
    @if($sale->delivery_method === 'pickup')<div class="mt-5 rounded-xl bg-white/5 p-4 text-sm"><h3 class="font-medium">Informasi pengambilan</h3>@if($sale->pickup_location && $sale->pickup_at)<p class="mt-2 whitespace-pre-line break-words text-stone-300">{{ $sale->pickup_location }}</p><p class="mt-2 text-champagne-300">{{ $sale->pickup_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</p>@else<p class="mt-2 text-stone-300">Lokasi dan jadwal belum ditetapkan. Tunggu pembaruan dari tim GRAIL.CO.</p>@endif</div>@endif
    @if($sale->paid_at)<p class="mt-5 text-xs text-stone-300">Pembayaran dicatat {{ $sale->paid_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB.</p>@endif
    @if(filled($sale->delivery_details['notes'] ?? null))<p class="mt-5 text-sm font-medium">Catatan</p><p class="mt-2 whitespace-pre-line break-words text-sm text-stone-300">{{ $sale->delivery_details['notes'] }}</p>@endif
</section>
@include('orders.tracking', ['sale' => $sale, 'showActor' => $showActor ?? false])
@endif
