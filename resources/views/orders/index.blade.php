@extends('layouts.app')
@section('title', 'My Orders — GRAIL.CO')
@section('content')
<section class="mx-auto max-w-5xl px-4 py-10 sm:px-6">
    <p class="eyebrow">Collection ledger</p>
    <h1 class="mt-2 text-3xl font-semibold">My acquisitions.</h1>
    @error('order')<p class="mt-6 rounded-xl border border-red-400/30 bg-red-400/5 p-4 text-sm text-red-300" role="alert">{{ $message }}</p>@enderror
    <div class="mt-8 space-y-4">
        @forelse($orders as $order)
            <article class="glass-panel rounded-2xl p-5">
                <div class="flex flex-col justify-between gap-3 border-b border-white/10 pb-4 sm:flex-row sm:items-center">
                    <div>
                        <p class="font-mono text-sm text-champagne-300"><a href="{{ route('orders.show', $order) }}" class="underline underline-offset-4">{{ $order->sale_number }}</a></p>
                        <p class="mt-1 text-xs text-stone-500">{{ $order->sold_at->copy()->timezone('Asia/Jakarta')->translatedFormat('d M Y, H:i') }} WIB</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="rounded-full px-2 py-1 text-xs {{ $order->status === 'completed' ? 'bg-emerald-400/10 text-emerald-300' : 'bg-red-400/10 text-red-300' }}">{{ $order->order_status_label }}</span>
                        <strong class="font-mono">Rp{{ number_format($order->total, 0, ',', '.') }}</strong>
                    </div>
                </div>
                <div class="mt-4 space-y-2">
                    @foreach($order->items as $item)
                        <div class="flex justify-between gap-5 text-sm"><span class="text-stone-300">{{ $item->product->name }} <span class="text-stone-500">× {{ $item->quantity }}</span></span><span class="font-mono text-xs text-stone-400">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</span></div>
                    @endforeach
                </div>
                @if($order->canCustomerCancel())
                    <div class="mt-5 border-t border-white/10 pt-4">
                        <form method="POST" action="{{ route('orders.cancel', $order) }}" data-confirm="Yakin ingin membatalkan pesanan ini? Pesanan yang dibatalkan tidak dapat dipulihkan.">
                            @csrf @method('PATCH')
                            <input type="hidden" name="version" value="{{ $order->order_version }}">
                            <button class="btn-ghost min-h-11 border-red-400/40 text-red-300">Batalkan pesanan</button>
                        </form>
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-white/10 py-16 text-center"><p class="text-stone-400">Belum ada acquisition tercatat.</p><a href="{{ route('catalog') }}" class="btn-gold mt-5">Explore vault</a></div>
        @endforelse
    </div>
    @if($orders->hasPages())<div class="mt-8">{{ $orders->links() }}</div>@endif
</section>
@endsection
