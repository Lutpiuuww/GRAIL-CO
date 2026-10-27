<!DOCTYPE html>
<html lang="id"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="GRAIL.CO back-office inventory operations.">
    <title>@yield('title', 'Back Office — GRAIL.CO')</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&family=Fira+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">@vite(['resources/css/app.css', 'resources/js/app.js'])
</head><body class="min-h-screen bg-obsidian-950">
@php($items = [
    ['dashboard','Overview','M3 13h8V3H3v10Zm10 8h8V11h-8v10Zm0-18v6h8V3h-8ZM3 21h8v-6H3v6Z'],
    ['products.index','Products','M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-12L4 7m8 4v10M4 7v10l8 4'],
    ['suppliers.index','Suppliers','M3 21h18M6 18V8m4 10V8m4 10V8m4 10V8M4 8h16L12 3 4 8Z'],
    ['purchases.index','Stock In','M12 3v12m0 0 5-5m-5 5-5-5M5 21h14'],
    ['sales.index','Sales','M3 10h18M7 15h2m-4 5h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2Z'],
])
@if(auth()->user()->role === 'admin')
    @php($items[] = ['users.index','Users','M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2m20 0v-2a4 4 0 00-3-3.87M9 11a4 4 0 100-8 4 4 0 000 8m8-8a4 4 0 010 8'])
@endif
<aside class="fixed inset-y-0 left-0 z-50 hidden w-64 border-r border-white/10 bg-obsidian-900 lg:flex lg:flex-col">
    <div class="flex h-20 items-center gap-3 border-b border-white/10 px-6"><span class="grid size-10 place-items-center rounded-full border border-champagne-400/50 font-mono text-champagne-300">G</span><div><p class="font-semibold tracking-[.16em]">GRAIL<span class="text-champagne-400">.CO</span></p><p class="font-mono text-[9px] uppercase tracking-widest text-stone-500">Back office</p></div></div>
    <nav class="flex-1 space-y-1 p-4" aria-label="Back-office navigation">@foreach($items as [$route,$label,$path])<a href="{{ route($route) }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors duration-200 {{ request()->routeIs(Str::before($route,'.').'.*') || request()->routeIs($route) ? 'bg-champagne-400/10 text-champagne-300' : 'text-stone-400 hover:bg-white/5 hover:text-stone-100' }}"><svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="{{ $path }}"/></svg>{{ $label }}</a>@endforeach
    @if(auth()->user()->role === 'admin')<a href="{{ route('categories.index') }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm transition-colors duration-200 {{ request()->routeIs('categories.*') ? 'bg-champagne-400/10 text-champagne-300' : 'text-stone-400 hover:bg-white/5 hover:text-stone-100' }}"><svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M7 7h.01M3 11l8.6-8.6A2 2 0 0113 2h6a3 3 0 013 3v6a2 2 0 01-.6 1.4L13 21a2 2 0 01-2.8 0L3 13.8A2 2 0 013 11Z"/></svg>Categories</a>@endif</nav>
    <div class="border-t border-white/10 p-4"><a href="{{ route('catalog') }}" class="btn-ghost w-full !justify-start !py-2.5 text-sm"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>View storefront</a></div>
</aside>
<div class="lg:pl-64"><header class="sticky top-0 z-40 flex h-16 items-center justify-between border-b border-white/10 bg-obsidian-950/90 px-4 backdrop-blur-xl sm:px-6 lg:h-20 lg:px-8"><a href="{{ route('dashboard') }}" class="font-semibold tracking-[.14em] mr-2 shrink-0 lg:hidden">GRAIL<span class="text-champagne-400">.CO</span></a><nav class="flex min-w-0 flex-1 gap-1 overflow-x-auto lg:hidden" aria-label="Modul back office">@foreach($items as [$route,$label])<a href="{{ route($route) }}" class="whitespace-nowrap rounded-lg px-3 py-2 text-xs {{ request()->routeIs($route) ? 'bg-white/10 text-champagne-300' : 'text-stone-400' }}">{{ $label }}</a>@endforeach</nav><div class="ml-auto hidden items-center gap-3 sm:flex"><div class="text-right"><p class="text-sm font-medium">{{ auth()->user()->name }}</p><p class="font-mono text-[9px] uppercase tracking-wider text-stone-500">{{ auth()->user()->role }}</p></div><form method="POST" action="{{ route('logout') }}" data-logout>@csrf<button class="grid size-10 place-items-center rounded-xl border border-white/10 text-stone-400 transition-colors hover:border-red-400/30 hover:text-red-300" aria-label="Logout"><svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M10 17l5-5-5-5m5 5H3m10-9h6a2 2 0 012 2v14a2 2 0 01-2 2h-6"/></svg></button></form></div></header>
<nav class="flex flex-wrap gap-2 border-b border-white/10 px-4 py-3 lg:hidden" aria-label="Navigasi akun dan administrasi">
    @if(auth()->user()->role === 'admin')<a href="{{ route('categories.index') }}" class="btn-ghost min-h-11 text-sm">Categories</a>@endif
    <a href="{{ route('profile.edit') }}" class="btn-ghost min-h-11 text-sm">Profile</a>
    <a href="{{ route('catalog') }}" class="btn-ghost min-h-11 text-sm">Catalog</a>
    <form method="POST" action="{{ route('logout') }}" data-logout>@csrf<button class="btn-ghost min-h-11 text-sm">Logout</button></form>
</nav>
<div class="mx-4 mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-champagne-400/20 bg-champagne-400/5 px-4 py-3 text-sm sm:mx-6 lg:mx-8" data-sales-live data-updates-url="{{ route('sales.updates') }}">
    <a href="{{ route('sales.index', ['fulfillment' => 'pending']) }}" class="inline-flex min-h-11 items-center gap-3 text-champagne-300 hover:text-white">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <span>Pesanan menunggu</span><strong class="grid min-w-6 place-items-center rounded-full bg-champagne-400 px-1.5 py-0.5 font-mono text-xs text-obsidian-950" data-sales-pending-count aria-label="Jumlah pesanan menunggu">…</strong>
    </a>
    <span class="text-xs text-stone-400" data-sales-live-status>Memeriksa pembaruan otomatis</span>
</div>
<div class="notice-card fixed bottom-5 left-4 right-4 z-[70] max-w-sm border-champagne-400/40 sm:left-auto sm:right-6" data-sales-toast role="status" hidden>
    <span class="grid size-10 shrink-0 place-items-center rounded-xl border border-champagne-400/25 bg-champagne-400/10 text-champagne-300"><svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/></svg></span>
    <div class="min-w-0 flex-1"><p class="font-mono text-[10px] font-semibold uppercase tracking-[.18em] text-champagne-400">Pesanan baru</p><p class="mt-1 text-sm leading-5 text-stone-100" data-sales-toast-message></p></div>
    <button type="button" class="notice-close" data-sales-toast-close aria-label="Tutup pemberitahuan"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg></button>
</div>
@include('components.flash-messages')
<main>@yield('content')</main></div>@include('components.logout-dialog')@include('components.confirm-dialog')
</body></html>
