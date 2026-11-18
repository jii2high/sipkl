<?php

namespace App\Actions\Auth;

use App\Actions\Action;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Langkah pertama login (TRD.md §7.1): validasi domain sebelum apa pun, lalu
 * kredensial. Respons tidak membedakan "akun tidak ada", "kata sandi salah", dan
 * "akun nonaktif" supaya tidak membocorkan keberadaan akun (BR-F1, NFR keamanan).
 */
final class AuthenticateUser extends Action
{
    public function handle(mixed ...$arguments): User
    {
        [$email, $password] = $arguments;

        $domain = (string) config('pkl.school.email_domain');
        $email = is_string($email) ? strtolower(trim($email)) : '';

        if (! str_ends_with($email, '@'.$domain)) {
            throw ValidationException::withMessages([
                'email' => __('pkl.email.domain', ['domain' => $domain]),
            ]);
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! $user->is_active || ! Hash::check((string) $password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('pkl.email.not_found'),
            ]);
        }

        return $user;
    }
}
