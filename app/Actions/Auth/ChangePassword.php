<?php

namespace App\Actions\Auth;

use App\Actions\Action;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Validation\ValidationException;

/**
 * Ganti kata sandi (BR-F1-4): minimal `pkl.password.min_length` karakter dan tidak
 * sama dengan NIS/NIP, dengan pesan validasi spesifik per pelanggaran.
 */
final class ChangePassword extends Action
{
    public function handle(mixed ...$arguments): User
    {
        [$user, $password] = $arguments;
        $password = (string) $password;
        $min = (int) config('pkl.password.min_length');

        if (mb_strlen($password) < $min) {
            throw ValidationException::withMessages([
                'password' => __('pkl.password.min', ['min' => $min]),
            ]);
        }

        if ($password === (string) $user->nis_nip) {
            throw ValidationException::withMessages([
                'password' => __('pkl.password.same_as_nis_nip'),
            ]);
        }

        return $this->transaction(function () use ($user, $password): User {
            // Cast `hashed` pada model meng-hash nilai ini; tidak ada plaintext di DB/log.
            $user->forceFill([
                'password' => $password,
                'is_first_login' => false,
            ])->save();

            app(AuditService::class)->log(
                description: 'Kata sandi pengguna diperbarui',
                subject: $user,
                event: 'password_diubah',
                causer: $user,
            );

            return $user;
        });
    }
}
