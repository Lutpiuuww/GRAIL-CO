<ol class="mb-8 grid grid-cols-3 gap-2 text-xs sm:gap-4 sm:text-sm" aria-label="Tahap checkout">
    @foreach([1 => 'Data penerima', 2 => 'Tinjau pesanan', 3 => 'Selesai'] as $index => $label)
        <li class="flex items-center gap-2 rounded-xl border px-3 py-3 {{ $step === $index ? 'border-champagne-400/40 bg-champagne-400/10 text-champagne-300' : 'border-white/10 text-stone-300' }}" @if($step === $index) aria-current="step" @endif><span class="grid size-6 shrink-0 place-items-center rounded-full border border-current font-mono">{{ $index }}</span><span>{{ $label }}</span></li>
    @endforeach
</ol>
