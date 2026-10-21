@extends('layouts.app')
@section('title', 'Profil — GRAIL.CO')
@section('content')
<section class="mx-auto max-w-4xl px-4 py-8 sm:py-12">
    <p class="eyebrow">Your collector account</p>
    <h1 class="mt-3 text-3xl font-semibold">Profil saya</h1>
    <p class="mt-2 text-sm text-stone-300">Kelola identitas dan keamanan akun. Peran akun: {{ ucfirst($user->role) }}.</p>
    <div class="mt-8 grid gap-6 md:grid-cols-2">
        <form method="POST" action="{{ route('profile.update') }}" class="glass-panel space-y-5 rounded-2xl p-6">
            @csrf @method('PATCH')
            <h2 class="text-xl font-semibold">Informasi pribadi</h2>
            <label class="block text-sm">Nama lengkap<input name="name" class="field mt-2" value="{{ old('name', $user->name) }}" autocomplete="name" maxlength="100" required></label>
            <label class="block text-sm">Email<input type="email" name="email" class="field mt-2" value="{{ old('email', $user->email) }}" autocomplete="email" maxlength="255" required></label>
            <label class="block text-sm">Kata sandi saat ini<input type="password" name="current_password" class="field mt-2" autocomplete="current-password" required></label>
            <p class="text-sm text-stone-300">Masukkan kata sandi untuk mengonfirmasi perubahan identitas.</p>
            <button class="btn-gold min-h-11 w-full">Simpan profil</button>
        </form>
        <form method="POST" action="{{ route('profile.password') }}" class="glass-panel space-y-5 rounded-2xl p-6">
            @csrf @method('PUT')
            <h2 class="text-xl font-semibold">Ganti kata sandi</h2>
            <label class="block text-sm">Kata sandi saat ini<input type="password" name="current_password" class="field mt-2" autocomplete="current-password" required></label>
            <label class="block text-sm">Kata sandi baru<input type="password" name="password" class="field mt-2" autocomplete="new-password" minlength="8" maxlength="128" required></label>
            <label class="block text-sm">Konfirmasi kata sandi baru<input type="password" name="password_confirmation" class="field mt-2" autocomplete="new-password" minlength="8" maxlength="128" required></label>
            <p class="text-sm text-stone-300">Gunakan minimal 8 karakter dan berbeda dari kata sandi lama.</p>
            <button class="btn-gold min-h-11 w-full">Perbarui kata sandi</button>
        </form>
    </div>
    <div class="mt-6 flex flex-wrap items-center gap-3">
        @if($user->isBackOffice())<a href="{{ route('dashboard') }}" class="btn-ghost min-h-11">Buka Back Office</a>@endif
        <form method="POST" action="{{ route('logout') }}" data-logout>@csrf<button class="btn-ghost min-h-11">Keluar dari akun</button></form>
    </div>
</section>
@endsection
