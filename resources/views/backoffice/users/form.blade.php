@extends('layouts.backoffice')
@section('title', ($user->exists ? 'Kelola akun' : 'Tambah pengguna').' — GRAIL.CO')
@section('content')
<section class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
    <a href="{{ route('users.index') }}" class="text-sm text-champagne-300">Kembali ke pengguna</a>
    <p class="eyebrow mt-6">Administration / Account</p><h1 class="mt-2 text-3xl font-semibold">{{ $user->exists ? 'Kelola akun' : 'Tambah pengguna' }}</h1>
    <form method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}" class="glass-panel mt-8 space-y-5 rounded-2xl p-6">
        @csrf @if($user->exists) @method('PUT') @endif
        <label class="block text-sm">Nama lengkap<input class="field mt-2" name="name" value="{{ old('name', $user->name) }}" maxlength="100" autocomplete="name" required>@error('name')<span class="mt-1 block text-red-300">{{ $message }}</span>@enderror</label>
        <label class="block text-sm">Email<input type="email" class="field mt-2" name="email" value="{{ old('email', $user->email) }}" maxlength="255" autocomplete="email" required>@error('email')<span class="mt-1 block text-red-300">{{ $message }}</span>@enderror</label>
        <label class="block text-sm">Role<select class="field mt-2" name="role" required>@foreach(['admin','staff','customer'] as $role)<option value="{{ $role }}" @selected(old('role', $user->role) === $role)>{{ ucfirst($role) }}</option>@endforeach</select>@error('role')<span class="mt-1 block text-red-300">{{ $message }}</span>@enderror</label>
        <label class="block text-sm">Status akun<select name="is_active" class="field mt-2" required><option value="1" @selected((string) old('is_active', (int) $user->is_active) === '1')>Aktif</option><option value="0" @selected((string) old('is_active', (int) $user->is_active) === '0')>Nonaktif</option></select></label>
        @unless($user->exists)
            <label class="block text-sm">Kata sandi awal<input type="password" name="password" class="field mt-2" minlength="8" maxlength="128" autocomplete="new-password" required></label>
            <label class="block text-sm">Konfirmasi kata sandi awal<input type="password" name="password_confirmation" class="field mt-2" minlength="8" maxlength="128" autocomplete="new-password" required></label>
        @endunless
        <p class="text-sm text-stone-300">Admin tidak dapat menonaktifkan atau menurunkan role akunnya sendiri. Pengguna mengganti kata sandinya melalui Profile.</p>
        <div class="flex flex-wrap gap-3"><button class="btn-gold">Simpan akun</button><a class="btn-ghost" href="{{ route('users.index') }}">Batal</a></div>
    </form>
</section>
@endsection
