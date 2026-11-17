<?php

namespace App\Livewire\Public;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class Login extends Component
{
    private const int MAX_ATTEMPTS = 5;

    private const int DECAY_SECONDS = 60;

    private const string SCHOOL_EMAIL_PATTERN = '/^[^@\s]+@smkn1kotabekasi\.sch\.id$/i';

    public string $email = '';

    public string $password = '';

    public ?string $status = null;

    public function submit(): void
    {
        $this->resetErrorBag();
        $this->status = null;

        $this->email = mb_strtolower(trim($this->email));
        $limiterKeys = $this->limiterKeys($this->email, (string) request()->ip());

        if (collect($limiterKeys)->contains(fn (string $key): bool => RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS))) {
            $this->addError('credentials', 'Terlalu banyak percobaan masuk. Silakan coba lagi sebentar.');

            return;
        }

        foreach ($limiterKeys as $key) {
            RateLimiter::hit($key, self::DECAY_SECONDS);
        }

        $validated = $this->validate([
            'email' => ['required', 'string', 'email', 'regex:'.self::SCHOOL_EMAIL_PATTERN],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'email.required' => 'Email sekolah wajib diisi.',
            'email.email' => 'Gunakan email sekolah yang valid.',
            'email.regex' => 'Gunakan email dengan domain @smkn1kotabekasi.sch.id.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (! Auth::guard('web')->validate($validated) || ! $user?->is_active) {
            $this->addError('credentials', 'Email atau password tidak sesuai.');

            return;
        }

        foreach ($limiterKeys as $key) {
            RateLimiter::clear($key);
        }

        $this->password = '';

        if ($user->is_first_login) {
            session()->put('otp_pending', true);
            session()->put('otp_email', $user->email);
            app(OtpService::class)->issue($user);

            $this->redirectRoute('verify-otp');

            return;
        }

        Auth::guard('web')->login($user);
        session()->regenerate();

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

    /**
     * @return list<string>
     */
    private function limiterKeys(string $email, string $ip): array
    {
        return [
            'login:account:'.hash('sha256', $email),
            'login:ip:'.hash('sha256', $ip),
        ];
    }

    public function render(): View
    {
        return view('livewire.public.login');
    }
}
