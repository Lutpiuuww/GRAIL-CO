@if(session('success') || $errors->any())
<div class="pointer-events-none fixed inset-x-4 top-20 z-[70] mx-auto flex max-w-md flex-col gap-3 sm:left-auto sm:right-6 sm:mx-0 sm:w-full" aria-label="Pemberitahuan">
    @if(session('success'))
    <div class="notice-card pointer-events-auto border-champagne-400/40" data-notice data-autodismiss="2000" role="status">
        <span class="grid size-10 shrink-0 place-items-center rounded-xl border border-champagne-400/25 bg-champagne-400/10 text-champagne-300">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg>
        </span>
        <div class="min-w-0 flex-1"><p class="font-mono text-[10px] font-semibold uppercase tracking-[.18em] text-champagne-400">Berhasil</p><p class="mt-1 text-sm leading-5 text-stone-100">{{ session('success') }}</p></div>
        <button type="button" class="notice-close" data-notice-close aria-label="Tutup pemberitahuan"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg></button>
    </div>
    @endif
    @if($errors->any())
    <div class="notice-card pointer-events-auto border-red-400/40" data-notice role="alert">
        <span class="grid size-10 shrink-0 place-items-center rounded-xl border border-red-400/25 bg-red-400/10 text-red-300">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v6m0 4h.01"/></svg>
        </span>
        <div class="min-w-0 flex-1"><p class="font-mono text-[10px] font-semibold uppercase tracking-[.18em] text-red-300">Periksa kembali</p><p class="mt-1 text-sm leading-5 text-stone-100">{{ $errors->first() }}</p></div>
        <button type="button" class="notice-close" data-notice-close aria-label="Tutup pemberitahuan"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg></button>
    </div>
    @endif
</div>
@endif
