<?php

namespace App\Livewire\Auth;

use App\Exceptions\OtpException;
use App\Models\User;
use App\Notifications\OtpNotification;
use App\Services\NotificationService;
use App\Services\OtpService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Tantangan OTP first-login `/login/otp` (TRD.md §7.1). Hanya dapat dibuka bila
 * ada pengguna tertunda dari langkah login. Verifikasi gagal menampilkan pesan
 * galat spesifik; kirim ulang tunduk limiter `otp-resend`.
 */
#[Layout('components.layouts.auth')]
#[Title('Verifikasi OTP')]
class OtpChallenge extends Component
{
    public string $code = '';

    public function mount(): void
    {
        if ($this->pendingUser() === null) {
            $this->redirectRoute('login');
        }
    }

    public function verify(OtpService $otp): void
    {
        $user = $this->pendingUser();

        if ($user === null) {
            $this->redirectRoute('login');

            return;
        }

        $this->validate(['code' => ['required', 'digits:6']]);

        try {
            $otp->verify($user, $this->code);
        } catch (OtpException $exception) {
            $this->addError('code', $exception->getMessage());

            return;
        }

        session()->put('auth.otp_verified', true);

        $this->redirectRoute('login.password');
    }

    public function resend(OtpService $otp, NotificationService $notifications): void
    {
        $user = $this->pendingUser();

        if ($user === null) {
            $this->redirectRoute('login');

            return;
        }

        try {
            $code = $otp->issue($user);
        } catch (OtpException $exception) {
            $this->addError('code', $exception->getMessage());

            return;
        }

        $notifications->send($user, new OtpNotification($code, (int) config('pkl.otp.internal.ttl_seconds')));

        $this->dispatch('otp-resent');
        session()->flash('status', __('pkl.otp.sent'));
    }

    private function pendingUser(): ?User
    {
        $id = session('auth.pending_user_id');

        return is_numeric($id) ? User::query()->find((int) $id) : null;
    }

    public function render()
    {
        return view('livewire.auth.otp-challenge', [
            'ttlSeconds' => (int) config('pkl.otp.internal.ttl_seconds'),
        ]);
    }
}
