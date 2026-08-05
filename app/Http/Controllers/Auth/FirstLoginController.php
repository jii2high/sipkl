<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Response;

class FirstLoginController extends Controller
{
    public function create(): Response
    {
        return inertia('auth/first-login');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $defaultPassword = $user->siswaProfile?->nis;
        $defaultPassword ??= $user->guruProfile?->nip;

        $request->validate([
            'password' => [
                'required',
                'confirmed',
                Password::defaults(),
                function ($attribute, $value, $fail) use ($defaultPassword) {
                    if ($defaultPassword && $value === $defaultPassword) {
                        $fail('Password baru tidak boleh sama dengan password awal (NIS/NIP).');
                    }
                },
            ],
        ]);

        $request->session()->forget('password_otp_verified_at');

        $user->password = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();

        return redirect()->route('dashboard');
    }
}
