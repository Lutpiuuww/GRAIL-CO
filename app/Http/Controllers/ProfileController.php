<?php

namespace App\Http\Controllers;

use App\Support\IndonesianRegions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user(), 'regions' => IndonesianRegions::all()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
            'phone' => ['nullable', 'string', 'regex:/^\+?[0-9][0-9 ()-]{7,19}$/'],
            'street_address' => ['nullable', 'string', 'min:10', 'max:500'],
            'address_province' => ['nullable', Rule::in(IndonesianRegions::provinces())],
            'address_city' => ['nullable', Rule::in(IndonesianRegions::cities($request->input('address_province')))],
            'address_postal_code' => ['nullable', 'regex:/^[0-9]{5}$/'],
            'current_password' => [Rule::requiredIf(fn () => $request->input('email') !== $request->user()->email), 'nullable', 'current_password'],
        ]);
        $request->user()->update(collect($data)->only(['name', 'email', 'phone', 'street_address', 'address_province', 'address_city', 'address_postal_code'])->all());

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'max:128', 'confirmed', 'different:current_password'],
        ]);
        $request->user()->update(['password' => $data['password'], 'remember_token' => null]);
        $request->session()->regenerate();

        return back()->with('success', 'Kata sandi berhasil diperbarui.');
    }
}
