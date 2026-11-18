<?php

namespace App\Livewire\Auth\Concerns;

use App\Actions\Auth\SwitchActiveRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Menyelesaikan login: menetapkan peran aktif default, meregenerasi sesi, dan
 * membersihkan state sementara alur first-login. Dipakai oleh halaman login dan
 * halaman paksa ganti kata sandi.
 */
trait CompletesLogin
{
    protected function completeLogin(User $user): void
    {
        $role = $user->defaultActiveRole();

        if ($role === null) {
            throw ValidationException::withMessages(['email' => __('pkl.role.none')]);
        }

        Auth::login($user);
        app(SwitchActiveRole::class)->handle($user, $role);

        session()->forget(['auth.pending_user_id', 'auth.otp_verified']);
    }

    protected function homeUrl(User $user): string
    {
        return $user->panelHomeUrl((string) session('active_role'));
    }
}
