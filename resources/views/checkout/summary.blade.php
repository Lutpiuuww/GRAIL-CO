<aside class="glass-panel h-fit rounded-2xl p-5 sm:p-6" aria-labelledby="order-summary-title">
    <p class="eyebrow">Your selection</p><h2 id="order-summary-title" class="mt-2 text-xl font-semibold">Ringkasan pesanan</h2>
    <ul class="mt-6 divide-y divide-white/10">@foreach($quote['items'] as $item)
        <li class="flex gap-3 py-4">
            @if($item['image_path'])<img class="size-16 shrink-0 rounded-xl object-cover" src="{{ asset($item['image_path']) }}" alt="{{ $item['name'] }}" width="64" height="64" loading="lazy">@endif
            <div class="min-w-0 flex-1"><a href="{{ route('catalog.show', $item['id']) }}" class="rounded text-sm font-medium hover:text-champagne-300">{{ $item['name'] }}</a><p class="mt-1 text-xs text-stone-300">{{ $item['quantity'] }} × Rp{{ number_format($item['unit_cents'] / 100, 0, ',', '.') }}</p><p class="mt-2 text-sm text-champagne-300">Rp{{ number_format($item['line_cents'] / 100, 0, ',', '.') }}</p></div>
        </li>
    @endforeach</ul>
    <dl class="mt-4 space-y-3 border-t border-white/10 pt-5 text-sm">
        <div class="flex justify-between gap-3"><dt>Subtotal barang</dt><dd>Rp{{ number_format($quote['subtotal_cents'] / 100, 0, ',', '.') }}</dd></div>
        <div class="flex justify-between gap-3"><dt>Ongkir</dt><dd>{{ ($preview ?? false) ? 'Pilih metode dahulu' : ($quote['shipping_cents'] ? 'Rp'.number_format($quote['shipping_cents'] / 100, 0, ',', '.') : 'Gratis') }}</dd></div>
        @unless($preview ?? false)<div class="flex flex-wrap justify-between gap-3 border-t border-white/10 pt-4 text-base font-semibold"><dt>Total pesanan</dt><dd class="font-mono text-champagne-300">Rp{{ number_format($quote['total_cents'] / 100, 0, ',', '.') }}</dd></div>@endunless
    </dl>
    <p class="mt-5 text-xs leading-6 text-stone-300">{{ ($preview ?? false) ? 'Total termasuk ongkir ditampilkan pada tahap tinjau pesanan.' : 'Tidak ada biaya tambahan di luar total yang ditampilkan.' }}</p>
</aside>
