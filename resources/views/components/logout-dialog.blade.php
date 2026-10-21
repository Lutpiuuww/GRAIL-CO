@auth
<dialog id="logout-dialog" aria-labelledby="logout-title" aria-describedby="logout-description" class="m-auto w-[calc(100%_-_2rem)] max-w-md rounded-3xl border border-champagne-400/25 bg-obsidian-900 p-6 text-stone-100 shadow-2xl backdrop:bg-black/75 backdrop:backdrop-blur-sm sm:p-8 print:hidden">
    <p class="eyebrow">GRAIL.CO / Account</p>
    <h2 id="logout-title" class="mt-3 text-2xl font-semibold">Yakin ingin keluar?</h2>
    <p id="logout-description" class="mt-3 text-sm leading-6 text-stone-300">Kamu akan keluar dari akun ini. Masuk kembali untuk melanjutkan belanja atau mengakses akun.</p>
    <div class="mt-7 flex flex-col gap-3 sm:flex-row">
        <button type="button" data-logout-cancel class="btn-ghost min-h-11 flex-1" autofocus>Tetap di sini</button>
        <button type="button" data-logout-accept class="btn-gold min-h-11 flex-1">Ya, keluar</button>
    </div>
</dialog>
@endauth
