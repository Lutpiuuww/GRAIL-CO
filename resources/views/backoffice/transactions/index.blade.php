@extends('layouts.backoffice')
@php($isPurchase = $type === 'purchase')
@php($prefix = $isPurchase ? 'purchases' : 'sales')
@section('title', ($isPurchase ? 'Stock In' : 'Sales').' — GRAIL.CO')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow">Transactions</p><h1 class="mt-2 text-3xl font-semibold">{{ $isPurchase ? 'Stock In' : 'Sales Ledger' }}</h1><p class="mt-2 text-sm text-stone-300">{{ $isPurchase ? 'Penerimaan barang dari supplier.' : 'Penjualan online dan transaksi kasir.' }}</p></div><a href="{{ route($prefix.'.create') }}" class="btn-gold">{{ $isPurchase ? 'Tambah Stock In' : 'Tambah penjualan' }}</a></div>
    <form class="glass-panel mt-8 grid gap-4 rounded-2xl p-5 sm:grid-cols-2 xl:grid-cols-4" method="GET" action="{{ route($prefix.'.index') }}">
        <label class="text-sm">Nomor transaksi<input class="field mt-2" name="q" type="search" value="{{ request('q') }}" maxlength="100"></label>
        <label class="text-sm">Status<select class="field mt-2" name="status"><option value="">Semua status</option><option value="completed" @selected(request('status') === 'completed')>Completed</option><option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option></select></label>
        <label class="text-sm">Dari tanggal<input class="field mt-2" type="date" name="from" value="{{ request('from') }}"></label>
        <label class="text-sm">Sampai tanggal<input class="field mt-2" type="date" name="to" value="{{ request('to') }}"></label>
        @unless($isPurchase)
            <label class="text-sm">Pemrosesan pesanan<select class="field mt-2" name="fulfillment"><option value="">Semua tahap</option>@foreach(\App\Models\Sale::FULFILLMENT_LABELS as $key => $label)<option value="{{ $key }}" @selected(request('fulfillment') === $key)>{{ $label }}</option>@endforeach</select></label>
            <label class="text-sm">Status pembayaran<select class="field mt-2" name="payment"><option value="">Semua pembayaran</option><option value="pending" @selected(request('payment') === 'pending')>Belum dikonfirmasi</option><option value="paid" @selected(request('payment') === 'paid')>Tercatat lunas</option><option value="refunded" @selected(request('payment') === 'refunded')>Pengembalian perlu diproses</option></select></label>
        @endunless
        <div class="flex items-end gap-2"><button class="btn-gold">Cari</button><a class="btn-ghost" href="{{ route($prefix.'.index') }}">Reset</a></div>
    </form>
    <div @unless($isPurchase) data-sales-results @endunless>
    <div class="glass-panel mt-6 overflow-x-auto rounded-2xl"><table class="w-full min-w-[860px] text-left text-sm">
        <caption class="sr-only">Riwayat {{ $isPurchase ? 'Stock In' : 'Sales' }}</caption>
        <thead class="border-b border-white/10 text-stone-300"><tr><th scope="col" class="px-5 py-4">Nomor</th><th scope="col" class="px-5 py-4">{{ $isPurchase ? 'Supplier' : 'Pemilik / pencatat' }}</th><th scope="col" class="px-5 py-4">Tanggal</th><th scope="col" class="px-5 py-4">Total</th><th scope="col" class="px-5 py-4">Status</th><th scope="col" class="px-5 py-4">Aksi</th></tr></thead>
        <tbody class="divide-y divide-white/10">@forelse($records as $record)<tr class="hover:bg-white/5">
            <td class="px-5 py-4 font-mono text-xs text-champagne-300"><a class="underline-offset-4 hover:underline" href="{{ route($prefix.'.show', $record) }}">{{ $isPurchase ? $record->purchase_number : $record->sale_number }}</a></td>
            <td class="px-5 py-4">{{ $isPurchase ? $record->supplier->name : $record->user->name }}</td><td class="px-5 py-4">{{ ($isPurchase ? $record->purchased_at : $record->sold_at)->copy()->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB</td><td class="whitespace-nowrap px-5 py-4">Rp{{ number_format($record->total,0,',','.') }}</td>
            <td class="px-5 py-4"><span class="rounded-full px-2 py-1 {{ $record->status === 'completed' ? 'bg-emerald-400/10 text-emerald-300' : 'bg-red-400/10 text-red-300' }}">{{ $isPurchase ? ucfirst($record->status) : $record->order_status_label }}</span>@if(!$isPurchase && $record->checkout_token)<p class="mt-3 text-xs text-stone-300">{{ $record->payment_status_label }}</p>@endif</td>
            <td class="px-5 py-4"><a class="btn-ghost min-h-11 text-xs" href="{{ route($prefix.'.show', $record) }}">Detail nota</a></td>
        </tr>@empty<tr><td colspan="6" class="px-5 py-12 text-center text-stone-300">Tidak ada transaksi yang sesuai filter.</td></tr>@endforelse</tbody>
    </table></div>
    <div class="mt-6">{{ $records->links() }}</div>
    </div>
</section>
@endsection
