@if($sale->checkout_token)
<section class="mt-6 space-y-5 rounded-2xl border border-champagne-400/25 p-5 sm:p-6 print:hidden" aria-labelledby="manage-order-title">
    <div><p class="eyebrow">Order operations</p><h2 id="manage-order-title" class="mt-2 text-xl font-semibold">Kelola pesanan customer</h2><p class="mt-3 text-sm leading-6 text-stone-300">Perubahan status dan catatan di bawah akan terlihat pada riwayat customer.</p></div>
    @if($sale->status === 'cancelled')<p class="text-sm text-red-300">Pesanan dibatalkan dan tidak dapat diproses lagi.</p>
    @else
        <div class="flex flex-wrap gap-3 text-sm"><span class="rounded-full border border-white/10 px-3 py-2">{{ $sale->order_status_label }}</span><span class="rounded-full border border-white/10 px-3 py-2">{{ $sale->payment_status_label }}</span></div>
        @if($sale->payment_status === 'pending')
            <form method="POST" action="{{ route('sales.payment', $sale) }}" class="rounded-xl border border-white/10 p-4" data-confirm="Tandai pembayaran pesanan ini lunas? Pastikan pembayaran telah dikonfirmasi sebelum melanjutkan.">
                @csrf @method('PATCH')<input type="hidden" name="version" value="{{ $sale->order_version }}">
                <h3 class="font-medium">Konfirmasi pembayaran</h3><p class="mt-2 text-sm leading-6 text-stone-300">Total: Rp{{ number_format($sale->total, 0, ',', '.') }}. Periksa pembayaran dan isi nomor referensi sebelum menandai lunas.</p>
                <label class="mt-4 block text-sm">Nomor referensi pembayaran<input class="field mt-2" name="reference" value="{{ is_scalar(old('reference', '')) ? old('reference', '') : '' }}" minlength="3" maxlength="80" placeholder="Contoh: TRF-2026-001" required></label>
                <label class="mt-4 block text-sm">Catatan untuk customer (opsional)<textarea class="field mt-2" name="note" rows="2" maxlength="500"></textarea></label>
                <button class="btn-gold mt-4 min-h-11">Tandai lunas</button>
            </form>
        @endif
        @php($next = $sale->nextFulfillmentStatus())
        @if($next)
            @php($paymentRequired = ($sale->payment_method === 'bank_transfer' || $next === 'completed') && $sale->payment_status !== 'paid')
            <form method="POST" action="{{ route('sales.fulfillment', $sale) }}" class="space-y-4 rounded-xl border border-white/10 p-4">
                @csrf @method('PATCH')<input type="hidden" name="version" value="{{ $sale->order_version }}"><input type="hidden" name="action" value="{{ $next }}">
                <h3 class="font-medium">Tahap berikutnya: {{ \App\Models\Sale::FULFILLMENT_LABELS[$next] }}</h3>
                @if($next === 'shipped')
                    <label class="block text-sm">Nama kurir<input class="field mt-2" name="carrier" maxlength="100" placeholder="Contoh: JNE" required></label>
                    <label class="block text-sm">Nomor resi<input class="field mt-2" name="tracking_number" maxlength="100" placeholder="Masukkan nomor resi" required></label>
                @endif
                @if($next === 'ready_for_pickup')
                    <label class="block text-sm">Lokasi pengambilan<textarea class="field mt-2" name="pickup_location" rows="2" minlength="5" maxlength="500" placeholder="Nama lokasi dan alamat lengkap" required></textarea></label>
                    <label class="block text-sm">Jadwal pengambilan (WIB)<input class="field mt-2" type="datetime-local" name="pickup_at" min="{{ now('Asia/Jakarta')->format('Y-m-d\TH:i') }}" required></label>
                @endif
                <label class="block text-sm">Catatan untuk customer (opsional)<textarea class="field mt-2" name="note" rows="2" maxlength="500" placeholder="Informasi pembaruan untuk customer"></textarea></label>
                @if($paymentRequired)<p class="text-sm leading-6 text-amber-300">Konfirmasi pembayaran terlebih dahulu. Transfer perlu lunas sebelum diproses; pembayaran saat diterima perlu lunas sebelum pesanan selesai.</p>@endif
                @if($next === 'completed')<p class="text-sm text-stone-300">Tandai selesai setelah barang diterima atau diambil.</p>@endif
                <button class="btn-gold min-h-11" @disabled($paymentRequired)>{{ $next === 'processing' ? 'Mulai proses pesanan' : ($next === 'shipped' ? 'Tandai dikirim' : ($next === 'ready_for_pickup' ? 'Tandai siap diambil' : 'Tandai selesai')) }}</button>
            </form>
        @else<p class="text-sm text-emerald-300">Pesanan sudah selesai. Riwayat tetap tersedia untuk diperiksa.</p>@endif
    @endif
</section>
@endif
