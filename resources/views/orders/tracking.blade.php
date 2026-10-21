@if($sale->checkout_token)
@php
    $stages = ['pending' => 'Konfirmasi', 'processing' => 'Diproses', ($sale->delivery_method === 'pickup' ? 'ready_for_pickup' : 'shipped') => ($sale->delivery_method === 'pickup' ? 'Siap diambil' : 'Dikirim'), 'completed' => 'Selesai'];
    $current = array_search($sale->fulfillment_status ?? 'pending', array_keys($stages), true);
@endphp
<section class="mt-6 rounded-2xl border border-white/10 p-5 print:hidden" aria-labelledby="tracking-title">
    <div class="flex flex-wrap items-center justify-between gap-3"><h2 id="tracking-title" class="text-lg font-semibold">Perkembangan pesanan</h2><span class="rounded-full px-3 py-2 text-xs {{ $sale->status === 'cancelled' ? 'bg-red-400/10 text-red-300' : 'bg-champagne-400/10 text-champagne-300' }}">{{ $sale->order_status_label }}</span></div>
    @if($sale->status !== 'cancelled')
        <ol class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4" aria-label="Tahap pemrosesan pesanan">
            @foreach($stages as $key => $label)
                <li class="rounded-xl border p-3 text-xs {{ $current !== false && $loop->index <= $current ? 'border-champagne-400/40 bg-champagne-400/5 text-champagne-300' : 'border-white/10 text-stone-300' }}" @if(($sale->fulfillment_status ?? 'pending') === $key) aria-current="step" @endif><span class="mb-2 block font-mono">{{ $loop->iteration }}{{ $loop->index < $current ? ' · Selesai' : '' }}</span>{{ $label }}</li>
            @endforeach
        </ol>
    @else<p class="mt-4 text-sm text-red-300">Pesanan dibatalkan. Detail pembatalan tercatat pada nota ini.</p>@endif
    <h3 class="mt-7 font-medium">Riwayat pembaruan</h3>
    <ol class="mt-4 space-y-5 border-l border-white/15 pl-5 text-sm">
        <li><p class="font-medium">Pesanan tercatat</p><time datetime="{{ $sale->sold_at->toIso8601String() }}" class="mt-1 block text-xs text-stone-300">{{ $sale->sold_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</time></li>
        @foreach($sale->events as $event)
            <li><p class="font-medium">{{ $event->label }}</p><time datetime="{{ $event->created_at->toIso8601String() }}" class="mt-1 block text-xs text-stone-300">{{ $event->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB @if($showActor ?? false) · {{ $event->user->name }} @endif</time>
                @if(filled($event->note))<p class="mt-2 whitespace-pre-line break-words leading-6 text-stone-300">{{ $event->note }}</p>@endif
                @if($event->type === 'shipped')<p class="mt-2 break-words text-stone-300">{{ $event->details['carrier'] }} · Resi: {{ $event->details['tracking_number'] }}</p>@endif
            </li>
        @endforeach
    </ol>
    <p class="mt-6 text-xs leading-6 text-stone-300">Status diperbarui oleh tim GRAIL.CO. Hubungi tim untuk informasi pengiriman terbaru.</p>
</section>
@endif
