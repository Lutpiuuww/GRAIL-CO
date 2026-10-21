@extends($layout)
@php
    $isPurchase = $type === 'purchase';
    $isOffice = $layout === 'layouts.backoffice';
    $number = $isPurchase ? $record->purchase_number : $record->sale_number;
    $date = $isPurchase ? $record->purchased_at : $record->sold_at;
    $prefix = $isPurchase ? 'purchases' : 'sales';
@endphp
@section('title', $number.' — GRAIL.CO')
@section('content')
<section class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 print:hidden">
        <a href="{{ route($isOffice ? $prefix.'.index' : 'orders') }}" class="btn-ghost">Kembali ke riwayat</a>
        <button type="button" data-print class="btn-gold">Cetak nota</button>
    </div>
    <article class="glass-panel rounded-2xl p-5 sm:p-8 receipt">
        <p class="eyebrow">GRAIL.CO / {{ $isPurchase ? 'Nota penerimaan barang' : 'Nota penjualan' }}</p>
        <h1 class="mt-3 break-all font-mono text-xl font-semibold sm:text-2xl">{{ $number }}</h1>
        <dl class="mt-6 grid gap-5 border-y border-white/10 py-5 text-sm sm:grid-cols-2">
            <div><dt class="text-stone-300">Tanggal</dt><dd class="mt-1">{{ $date->copy()->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB</dd></div>
            <div><dt class="text-stone-300">Status</dt><dd class="mt-1 {{ $record->status === 'completed' ? 'text-emerald-300' : 'text-red-300' }}">{{ $isPurchase ? ucfirst($record->status) : $record->order_status_label }}</dd></div>
            <div><dt class="text-stone-300">{{ $isPurchase ? 'Dicatat oleh' : 'Pemilik transaksi / pencatat' }}</dt><dd class="mt-1 break-words">{{ $record->user->name }}</dd></div>
            @if($isPurchase)<div><dt class="text-stone-300">Supplier</dt><dd class="mt-1">{{ $record->supplier->name }}</dd></div>@endif
        </dl>
        <div class="mt-6 overflow-x-auto">
            <table class="w-full min-w-[540px] text-left text-sm receipt-table">
                <caption class="sr-only">Rincian item transaksi {{ $number }}</caption>
                <thead class="border-b border-white/10 text-stone-300"><tr><th scope="col" class="py-3 pr-4">Produk</th><th scope="col" class="px-3 py-3 text-right">Jumlah</th><th scope="col" class="px-3 py-3 text-right">Harga satuan</th><th scope="col" class="py-3 pl-3 text-right">Subtotal</th></tr></thead>
                <tbody class="divide-y divide-white/10">@foreach($record->items as $item)<tr>
                    <td class="py-4 pr-4"><p>{{ $item->product->name }}</p><p class="mt-1 font-mono text-xs text-stone-300">{{ $item->product->sku }}</p>@if($item->product->trashed())<span class="text-xs text-amber-300">Produk diarsipkan</span>@endif</td>
                    <td class="px-3 py-4 text-right">{{ $item->quantity }}</td><td class="whitespace-nowrap px-3 py-4 text-right">Rp{{ number_format($isPurchase ? $item->unit_cost : $item->unit_price, 0, ',', '.') }}</td><td class="whitespace-nowrap py-4 pl-3 text-right">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>@endforeach</tbody>
                <tfoot class="border-t border-white/10">
                    @if(!$isPurchase && $record->checkout_token)<tr><th colspan="3" scope="row" class="pt-4 pr-3 text-right font-normal">Subtotal barang</th><td class="pt-4 pl-3 text-right">Rp{{ number_format($record->items->sum('subtotal'), 0, ',', '.') }}</td></tr><tr><th colspan="3" scope="row" class="pt-3 pr-3 text-right font-normal">Biaya pengiriman</th><td class="pt-3 pl-3 text-right">Rp{{ number_format($record->shipping_cost, 0, ',', '.') }}</td></tr>@endif
                    <tr><th colspan="3" scope="row" class="py-5 pr-3 text-right">Total transaksi</th><td class="whitespace-nowrap py-5 pl-3 text-right font-semibold text-champagne-300">Rp{{ number_format($record->total, 0, ',', '.') }}</td></tr></tfoot>
            </table>
        </div>
        @unless($isPurchase) @include('orders.delivery-details', ['sale' => $record, 'showActor' => $isOffice]) @endunless
        @if($record->status === 'cancelled')
            <section class="mt-6 rounded-xl border border-red-400/30 bg-red-400/5 p-4" aria-label="Riwayat pembatalan">
                <h2 class="font-semibold text-red-300">Transaksi dibatalkan</h2>
                @if($record->cancelled_at)
                    <p class="mt-2 text-sm">Oleh {{ $record->cancelledBy->name }} pada {{ $record->cancelled_at->copy()->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB.</p>
                    <p class="mt-2 whitespace-pre-wrap break-words text-sm">{{ $record->cancellation_reason }}</p>
                @else<p class="mt-2 text-sm text-stone-300">Pembatalan lama belum memiliki catatan petugas, waktu, dan alasan.</p>@endif
            </section>
        @endif
    </article>
    @if($isOffice && !$isPurchase) @include('backoffice.transactions.order-management', ['sale' => $record]) @endif
    @if($isOffice && auth()->user()->role === 'admin' && ($isPurchase ? $record->status === 'completed' : $record->canBeVoided()))
        <details class="mt-6 rounded-2xl border border-red-400/30 p-5 print:hidden" @if($errors->has('cancellation_reason')) open @endif>
            <summary class="cursor-pointer py-2 text-red-300">Batalkan transaksi dan sesuaikan stok</summary>
            <form method="POST" action="{{ route($prefix.'.cancel', $record) }}" class="mt-4 space-y-4" data-confirm="Batalkan transaksi ini? Stok akan disesuaikan dan alasan akan tersimpan.">
                @csrf @method('PATCH')
                <p class="text-sm text-stone-300">{{ $isPurchase ? 'Jumlah penerimaan akan ditarik kembali dari stok. Stok saat ini harus mencukupi.' : 'Jumlah barang yang dijual akan dikembalikan ke stok.' }}</p>
                <label class="block text-sm">Alasan pembatalan<textarea class="field mt-2" name="cancellation_reason" rows="3" minlength="5" maxlength="1000" required>{{ old('cancellation_reason') }}</textarea>@error('cancellation_reason')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
                <button class="btn-ghost border-red-400/30 text-red-300">Konfirmasi pembatalan</button>
            </form>
        </details>
    @endif
</section>
@endsection
