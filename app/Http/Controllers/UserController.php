<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(['admin', 'staff', 'customer'])],
            'active' => ['nullable', Rule::in(['0', '1'])],
        ]);
        $users = User::query()
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', '%'.$term.'%')))
            ->when($filters['role'] ?? null, fn ($q, $role) => $q->where('role', $role))
            ->when(isset($filters['active']), fn ($q) => $q->where('is_active', $filters['active']))
            ->latest('id')->paginate(12)->withQueryString();

        return view('backoffice.users.index', compact('users'));
    }

    public function create(): View
    {
        return view('backoffice.users.form', ['user' => new User(['role' => 'customer'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        User::create($this->validated($request));

        return to_route('users.index')->with('success', 'Akun pengguna berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        return view('backoffice.users.form', compact('user'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $this->validated($request, $user);
        DB::transaction(function () use ($request, $user, $data) {
            // Serialize account changes involving administrators and recheck the actor after locking.
            $admins = User::where('role', 'admin')->orderBy('id')->lockForUpdate()->get();
            $actor = $admins->firstWhere('id', $request->user()->id);
            abort_unless($actor?->is_active, 403);
            $target = User::lockForUpdate()->findOrFail($user->id);
            if ($target->is($actor) && ($data['role'] !== 'admin' || ! $data['is_active'])) {
                throw ValidationException::withMessages(['role' => 'Admin tidak dapat menurunkan role atau menonaktifkan akunnya sendiri.']);
            }
            $target->update($data);
            if (! $target->is_active) {
                $target->forceFill(['remember_token' => null])->save();
            }
        });

        return to_route('users.index')->with('success', 'Akun pengguna berhasil diperbarui.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        // Passwords are managed by the account owner through Profile after account creation.
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user?->id)],
            'role' => ['required', Rule::in(['admin', 'staff', 'customer'])],
            'is_active' => ['required', 'boolean'],
        ];
        if (! $user) {
            $rules['password'] = ['required', 'string', 'confirmed', 'min:8', 'max:128'];
        }

        return $request->validate($rules);
    }
}
