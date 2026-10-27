@extends('layouts.app')
@section('title', 'Profil — GRAIL.CO')
@section('content')
<section class="mx-auto max-w-4xl px-4 py-8 sm:py-12">
    <p class="eyebrow">Your collector account</p>
    <h1 class="mt-3 text-3xl font-semibold">Profil saya</h1>
    <p class="mt-2 text-sm text-stone-300">Kelola identitas, kontak, dan keamanan akun. Peran akun: {{ ucfirst($user->role) }}.</p>
    <div class="mt-8 grid gap-6 md:grid-cols-2">
        <form method="POST" action="{{ route('profile.update') }}" class="glass-panel space-y-5 rounded-2xl p-6">
            @csrf @method('PATCH')
            <h2 class="text-xl font-semibold">Informasi pribadi</h2>
            <label class="block text-sm">Nama lengkap<input name="name" class="field mt-2" value="{{ old('name', $user->name) }}" autocomplete="name" maxlength="100" required></label>
            <label class="block text-sm">Email<input type="email" name="email" class="field mt-2" value="{{ old('email', $user->email) }}" autocomplete="email" maxlength="255" required></label>
            <label class="block text-sm">Nomor telepon <span class="text-stone-400">(opsional)</span><input type="tel" name="phone" class="field mt-2" value="{{ old('phone', $user->phone) }}" autocomplete="tel" maxlength="21" placeholder="Contoh: 081234567890">@error('phone')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
            <div class="space-y-4 border-t border-white/10 pt-5" data-profile-region>
                <div><h3 class="font-medium">Alamat utama</h3><p class="mt-1 text-sm leading-6 text-stone-300">Lengkapi sekali agar alamat ini bisa dipilih langsung saat checkout. Semua kolom alamat bersifat opsional.</p></div>
                <label class="block text-sm">Provinsi<select name="address_province" class="field mt-2 cursor-pointer" autocomplete="address-level1" data-region-province><option value="">Pilih provinsi</option>@foreach(array_keys($regions) as $province)<option value="{{ $province }}" @selected(old('address_province', $user->address_province) === $province)>{{ $province }}</option>@endforeach</select>@error('address_province')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
                <label class="block text-sm">Kota / kabupaten<select name="address_city" class="field mt-2 cursor-pointer" autocomplete="address-level2" data-region-city><option value="">Pilih kota / kabupaten</option>@foreach($regions[old('address_province', $user->address_province)] ?? [] as $city)<option value="{{ $city }}" @selected(old('address_city', $user->address_city) === $city)>{{ $city }}</option>@endforeach</select>@error('address_city')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
                <label class="block text-sm">Alamat jalan dan nomor rumah<textarea name="street_address" class="field mt-2" autocomplete="street-address" maxlength="500" rows="3" placeholder="Nama jalan, nomor rumah, RT/RW, dan kecamatan">{{ old('street_address', $user->street_address) }}</textarea>@error('street_address')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
                <label class="block text-sm">Kode pos<input name="address_postal_code" class="field mt-2" autocomplete="postal-code" inputmode="numeric" pattern="[0-9]{5}" maxlength="5" value="{{ old('address_postal_code', $user->address_postal_code) }}">@error('address_postal_code')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
                @if($user->hasSavedDeliveryAddress())<p class="text-sm text-emerald-300">Alamat utama siap dipakai saat checkout.</p>@else<p class="text-sm text-stone-300">Jika alamat belum lengkap, checkout tetap menyediakan formulir alamat manual.</p>@endif
            </div>
            <label class="block text-sm">Kata sandi saat ini <span class="text-stone-400">(hanya jika mengganti email)</span><input type="password" name="current_password" class="field mt-2" autocomplete="current-password">@error('current_password')<span class="mt-2 block text-red-300">{{ $message }}</span>@enderror</label>
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
