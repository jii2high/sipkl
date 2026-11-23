<?php

namespace App\Livewire\Auth;

use App\Actions\Auth\AuthenticateUser;
use App\Livewire\Auth\Concerns\CompletesLogin;
use App\Models\User;
use App\Notifications\OtpNotification;
use App\Services\NotificationService;
use App\Services\OtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Halaman login publik `/login` (TRD.md §7.1). Domain dan kredensial divalidasi
 * sebelum OTP; akun `is_first_login` diarahkan ke tantangan OTP.
 */
#[Layout('components.layouts.auth')]
#[Title('Masuk')]
class Login extends Component
{
    use CompletesLogin;

    public string $email = '';

    public string $password = '';

    public function mount(): void
    {
        if (Auth::check()) {
            $this->redirect($this->homeUrl(Auth::user()));
        }
    }

    public function submit(OtpService $otp, NotificationService $notifications): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Batas percobaan per (email + IP) sebelum kunci sementara (TRD.md A16).
        $key = 'login:'.Str::lower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, (int) config('pkl.login.max_attempts'))) {
            throw ValidationException::withMessages([
                'email' => __('pkl.auth.throttled', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        try {
            $user = app(AuthenticateUser::class)->handle($this->email, $this->password);
        } catch (ValidationException $exception) {
            RateLimiter::hit($key, (int) config('pkl.login.decay_seconds'));

            throw $exception;
        }

        RateLimiter::clear($key);

        if ($user->is_first_login) {
            $this->beginFirstLogin($user, $otp, $notifications);

            return;
        }

        $this->completeLogin($user);
        $this->redirect($this->homeUrl($user));
    }

    private function beginFirstLogin(User $user, OtpService $otp, NotificationService $notifications): void
    {
        $code = $otp->issue($user);

        $notifications->send($user, new OtpNotification($code, (int) config('pkl.otp.internal.ttl_seconds')));

        session()->put('auth.pending_user_id', $user->getKey());
        session()->put('auth.otp_verified', false);

        $this->redirectRoute('login.otp');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
