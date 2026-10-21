@extends('layouts.backoffice')
@section('title', 'Users — GRAIL.CO')
@section('content')
<section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="eyebrow">Administration</p><h1 class="mt-2 text-3xl font-semibold">Manajemen pengguna</h1><p class="mt-2 text-sm text-stone-300">Kelola akses akun. Akun nonaktif tetap tersimpan dalam riwayat transaksi.</p></div>
        <a href="{{ route('users.create') }}" class="btn-gold">Tambah pengguna</a>
    </div>
    <form class="glass-panel mt-8 grid gap-4 rounded-2xl p-5 sm:grid-cols-2 lg:grid-cols-4" method="GET" action="{{ route('users.index') }}">
        <label class="text-sm">Cari nama atau email<input class="field mt-2" type="search" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Nama atau email"></label>
        <label class="text-sm">Role<select class="field mt-2" name="role"><option value="">Semua role</option>@foreach(['admin','staff','customer'] as $role)<option value="{{ $role }}" @selected(request('role') === $role)>{{ ucfirst($role) }}</option>@endforeach</select></label>
        <label class="text-sm">Status akun<select class="field mt-2" name="active"><option value="">Semua status</option><option value="1" @selected(request('active') === '1')>Aktif</option><option value="0" @selected(request('active') === '0')>Nonaktif</option></select></label>
        <div class="flex items-end gap-2"><button class="btn-gold">Terapkan</button><a href="{{ route('users.index') }}" class="btn-ghost">Reset</a></div>
    </form>
    <div class="glass-panel mt-6 overflow-x-auto rounded-2xl">
        <table class="w-full min-w-[680px] text-left text-sm">
            <caption class="sr-only">Daftar pengguna GRAIL.CO</caption>
            <thead class="border-b border-white/10 text-stone-300"><tr><th scope="col" class="px-5 py-4">Nama</th><th scope="col" class="px-5 py-4">Email</th><th scope="col" class="px-5 py-4">Role</th><th scope="col" class="px-5 py-4">Status</th><th scope="col" class="px-5 py-4">Aksi</th></tr></thead>
            <tbody class="divide-y divide-white/10">@forelse($users as $user)
                <tr class="hover:bg-white/5"><td class="px-5 py-4 font-medium">{{ $user->name }} @if($user->id === auth()->id())<span class="text-champagne-300">(Anda)</span>@endif</td><td class="px-5 py-4">{{ $user->email }}</td><td class="px-5 py-4">{{ ucfirst($user->role) }}</td><td class="px-5 py-4"><span class="rounded-full px-3 py-1 {{ $user->is_active ? 'bg-emerald-400/10 text-emerald-300' : 'bg-red-400/10 text-red-300' }}">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td class="px-5 py-4"><a class="btn-ghost min-h-11 text-xs" href="{{ route('users.edit', $user) }}" aria-label="Kelola akun {{ $user->name }}">Kelola</a></td></tr>
            @empty<tr><td colspan="5" class="px-5 py-12 text-center text-stone-300">Tidak ada pengguna yang sesuai filter.</td></tr>@endforelse</tbody>
        </table>
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
</section>
@endsection
