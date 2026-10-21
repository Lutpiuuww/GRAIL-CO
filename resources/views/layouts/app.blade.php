<!DOCTYPE html>
<html lang="id"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="GRAIL.CO — curated archive streetwear and luxury horology.">
    <title>@yield('title', 'GRAIL.CO')</title><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500;600&family=Fira+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">@vite(['resources/css/app.css', 'resources/js/app.js'])
</head><body class="min-h-screen bg-[radial-gradient(circle_at_80%_0%,rgba(185,151,70,.12),transparent_32%),radial-gradient(circle_at_0%_45%,rgba(255,255,255,.04),transparent_25%)]">
<header class="sticky top-0 z-50 border-b border-white/10 bg-obsidian-950/85 backdrop-blur-xl"><div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
    <a href="{{ route('catalog') }}" class="flex items-center gap-3 rounded-lg"><span class="grid size-9 place-items-center rounded-full border border-champagne-400/50 font-mono text-sm font-semibold text-champagne-300">G</span><span class="font-semibold tracking-[.18em]">GRAIL<span class="text-champagne-400">.CO</span></span></a>
    <nav class="hidden items-center gap-1 md:flex" aria-label="Navigasi utama"><a href="{{ route('catalog') }}" class="rounded-lg px-3 py-2 text-sm {{ request()->routeIs('catalog') ? 'bg-white/10 text-champagne-300' : 'text-stone-400 hover:text-white' }}">Catalog</a>
    @auth @if(auth()->user()->isBackOffice())<a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2 text-sm {{ request()->routeIs('dashboard') ? 'bg-white/10 text-champagne-300' : 'text-stone-400 hover:text-white' }}">Back Office</a>@endif
    <a href="{{ route('profile.edit') }}" class="rounded-lg px-3 py-2 text-sm text-stone-300 hover:text-white">Profile</a><a href="{{ route('orders') }}" class="rounded-lg px-3 py-2 text-sm text-stone-400 hover:text-white">My Orders</a><a href="{{ route('cart') }}" class="btn-ghost !px-3 !py-2 text-sm">Cart <span class="rounded-full bg-champagne-400 px-1.5 text-xs text-obsidian-950">{{ array_sum(session('cart', [])) }}</span></a><form method="POST" action="{{ route('logout') }}" data-logout>@csrf<button class="rounded-lg px-3 py-2 text-sm text-stone-400 hover:text-white">Logout</button></form>
    @else<a href="{{ route('login') }}" class="btn-gold !px-4 !py-2 text-sm">Masuk</a>@endauth</nav>
</div></header>
@if(session('success'))<div role="status" class="mx-auto mt-4 max-w-7xl px-4"><div class="rounded-xl border border-emerald-400/20 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-300">{{ session('success') }}</div></div>@endif
@if($errors->any())<div role="alert" class="mx-auto mt-4 max-w-7xl px-4"><div class="rounded-xl border border-red-400/20 bg-red-400/10 px-4 py-3 text-sm text-red-300">{{ $errors->first() }}</div></div>@endif
<main class="pb-24 md:pb-10">@yield('content')</main>
<nav class="fixed inset-x-4 bottom-4 z-50 grid grid-cols-4 rounded-2xl border border-white/10 bg-obsidian-800/95 p-2 shadow-2xl backdrop-blur-xl md:hidden" aria-label="Navigasi mobile">
@php($nav = [['catalog','Catalog','M4 6h16M4 12h16M4 18h16'],['cart','Cart','M3 3h2l.4 2M7 13h10l4-8H5.4M7 13 5.4 5M7 13l-2 2h14M9 19.5h.01M17 19.5h.01'],['orders','Orders','M9 12h6m-6 4h6M7 3h10a2 2 0 012 2v16l-4-2-3 2-3-2-4 2V5a2 2 0 012-2'],[auth()->check()?'profile.edit':'login',auth()->check()?'Profile':'Masuk','M20 21a8 8 0 10-16 0m8-9a4 4 0 100-8 4 4 0 000 8']])
@foreach($nav as [$route,$label,$path]) @if(!auth()->check() && in_array($route,['cart','orders'])) @continue @endif
<a href="{{ route($route) }}" class="flex flex-col items-center gap-1 rounded-xl px-2 py-2 text-[10px] {{ request()->routeIs($route) ? 'bg-white/10 text-champagne-300' : 'text-stone-400' }}"><svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="{{ $path }}"/></svg>{{ $label }}</a>@endforeach
</nav>@include('components.logout-dialog')
</body></html>
