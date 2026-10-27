@error('order')
    <p class="mt-5 rounded-xl border border-red-400/30 bg-red-400/5 p-4 text-sm text-red-300" role="alert">{{ $message }}</p>
@enderror
@if($sale->canCustomerCancel())
    <section class="mt-6 rounded-xl border border-red-400/25 bg-red-400/5 p-4 print:hidden" aria-label="Pembatalan pesanan">
        <h2 class="font-semibold">Perlu membatalkan pesanan?</h2>
        <p class="mt-2 text-sm leading-6 text-stone-300">Pesanan masih menunggu konfirmasi penjual. Jika dibatalkan sekarang, stok barang akan dikembalikan.</p>
        <form method="POST" action="{{ route('orders.cancel', $sale) }}" class="mt-4" data-confirm="Yakin ingin membatalkan pesanan ini? Pesanan yang dibatalkan tidak dapat dipulihkan.">
            @csrf @method('PATCH')
            <input type="hidden" name="version" value="{{ $sale->order_version }}">
            <button class="btn-ghost min-h-11 border-red-400/40 text-red-300">Batalkan pesanan</button>
        </form>
    </section>
@endif
