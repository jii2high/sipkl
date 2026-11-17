<?php

namespace App\Livewire\Public;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class ForcePasswordReset extends Component
{
    public string $password = '';

    public string $password_confirmation = '';

    public function submit(): void
    {
        $this->resetErrorBag();

        $userId = session('password_reset_user_id');
        $user = $userId ? User::query()->find($userId) : null;

        if ($user === null) {
            $this->addError('password', 'Sesi perubahan password tidak valid. Silakan ulang login.');

            return;
        }

        $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        if ($this->password === $user->nis_nip) {
            $this->addError('password', 'Password baru tidak boleh sama dengan NIS/NIP.');

            return;
        }

        $user->forceFill([
            'password' => $this->password,
            'is_first_login' => false,
        ])->save();

        session()->forget(['password_reset_pending', 'password_reset_user_id']);
        Auth::guard('web')->login($user);

        $role = $user->roles()->first()?->role;

        if ($role === null) {
            $role = match ($user->user_type) {
                'hubin' => 'hubin',
                'kepsek' => 'kepsek',
                default => 'guru_pembimbing',
            };
        }

        session(['active_role' => $role]);

        $this->redirect($role === 'hubin' ? '/hubin' : '/app');
    }

    public function render(): View
    {
        return view('livewire.public.force-password-reset');
    }
}
