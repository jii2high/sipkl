<?php

namespace App\Livewire\Public;

use App\Models\User;
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
        $this->status = 'Kredensial valid. Proses verifikasi lanjutan belum tersedia.';
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
