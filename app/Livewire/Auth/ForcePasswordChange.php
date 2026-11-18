<?php

namespace App\Livewire\Auth;

use App\Actions\Auth\ChangePassword;
use App\Livewire\Auth\Concerns\CompletesLogin;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Paksa ganti kata sandi setelah OTP `/login/password` (TRD.md §7.1). Hanya dapat
 * dibuka bila OTP sudah terverifikasi pada sesi ini.
 */
#[Layout('components.layouts.auth')]
#[Title('Ganti Kata Sandi')]
class ForcePasswordChange extends Component
{
    use CompletesLogin;

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        if (! session()->get('auth.otp_verified') || $this->pendingUser() === null) {
            $this->redirectRoute('login');
        }
    }

    public function submit(): void
    {
        $user = $this->pendingUser();

        if ($user === null) {
            $this->redirectRoute('login');

            return;
        }

        $this->validate([
            'password' => ['required', 'string', 'confirmed'],
        ]);

        try {
            app(ChangePassword::class)->handle($user, $this->password);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            return;
        }

        $this->completeLogin($user->fresh());
        $this->redirect($this->homeUrl($user));
    }

    private function pendingUser(): ?User
    {
        $id = session('auth.pending_user_id');

        return is_numeric($id) ? User::query()->find((int) $id) : null;
    }

    public function render()
    {
        return view('livewire.auth.force-password-change');
    }
}
