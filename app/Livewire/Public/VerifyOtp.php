<?php

namespace App\Livewire\Public;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class VerifyOtp extends Component
{
    public string $code = '';

    public ?string $message = null;

    public function submit(): void
    {
        $this->resetErrorBag();
        $this->message = null;

        $this->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Kode OTP wajib diisi.',
            'code.digits' => 'Kode OTP harus 6 digit angka.',
        ]);

        $email = session('otp_email');
        $user = $email ? User::query()->where('email', $email)->first() : null;

        if ($user === null) {
            $this->addError('code', 'Sesi OTP tidak valid. Silakan ulang login.');

            return;
        }

        if (! app(OtpService::class)->verify($user, $this->code)) {
            $this->addError('code', 'Kode OTP salah atau kedaluwarsa.');

            return;
        }

        $user->forceFill(['email_verified_at' => now()])->save();
        session()->forget(['otp_pending', 'otp_email']);
        session(['password_reset_pending' => true, 'password_reset_user_id' => $user->getKey()]);

        $this->redirectRoute('force-password-reset');
    }

    public function resend(): void
    {
        $this->resetErrorBag();
        $this->message = null;

        $email = session('otp_email');
        $user = $email ? User::query()->where('email', $email)->first() : null;

        if ($user === null) {
            $this->addError('resend', 'Sesi OTP tidak valid. Silakan ulang login.');

            return;
        }

        try {
            app(OtpService::class)->resend($user);
            $this->message = 'Kode OTP baru telah dikirim. Silakan cek email Anda.';
        } catch (\DomainException $e) {
            $this->addError('resend', $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.public.verify-otp');
    }
}
