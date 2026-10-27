<dialog id="confirm-dialog" aria-labelledby="confirm-title" aria-describedby="confirm-description" class="m-auto w-[calc(100%_-_2rem)] max-w-md rounded-3xl border border-champagne-400/25 bg-obsidian-900 p-6 text-stone-100 shadow-2xl backdrop:bg-black/75 backdrop:backdrop-blur-sm sm:p-8 print:hidden">
    <span class="grid size-12 place-items-center rounded-2xl border border-champagne-400/25 bg-champagne-400/10 text-champagne-300"><svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3 3 7v5c0 5 3.6 8.5 9 9 5.4-.5 9-4 9-9V7l-9-4Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5m0 3h.01"/></svg></span>
    <p class="eyebrow mt-5">GRAIL.CO / Konfirmasi</p>
    <h2 id="confirm-title" class="mt-2 text-2xl font-semibold">Konfirmasi tindakan</h2>
    <p id="confirm-description" class="mt-3 text-sm leading-6 text-stone-300" data-confirm-message></p>
    <div class="mt-7 flex flex-col gap-3 sm:flex-row">
        <button type="button" data-confirm-cancel class="btn-ghost min-h-11 flex-1" autofocus>Kembali</button>
        <button type="button" data-confirm-accept class="btn-gold min-h-11 flex-1">Ya, lanjutkan</button>
    </div>
</dialog>
