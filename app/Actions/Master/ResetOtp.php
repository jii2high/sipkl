<?php

namespace App\Actions\Master;

use App\Actions\Action;
use App\Models\User;
use App\Services\AuditService;
use RuntimeException;

/**
 * Reset akses OTP sebuah akun (T2.2). OTP aktif dibatalkan dan akun dikembalikan ke
 * keadaan "login pertama", sehingga login berikutnya melewati OTP lalu paksa ganti
 * kata sandi (BR-F1-2). Kode baru diterbitkan saat pengguna login, bukan di sini,
 * supaya Hubin tidak pernah melihat rahasia pengguna lain (TRD.md §12).
 */
final class ResetOtp extends Action
{
    public function handle(mixed ...$arguments): User
    {
        [$user, $actor] = $arguments + [null, null];

        if (! $user instanceof User) {
            throw new RuntimeException('Akun tidak dikenal.');
        }

        return $this->transaction(function () use ($user, $actor): User {
            $invalidated = $user->otpCodes()
                ->whereNull('verified_at')
                ->whereNull('invalidated_at')
                ->update(['invalidated_at' => now()]);

            $alreadyReset = (bool) $user->is_first_login;

            if ($invalidated === 0 && $alreadyReset) {
                return $user;
            }

            $user->forceFill(['is_first_login' => true])->save();

            app(AuditService::class)->log(
                description: "Akses OTP akun \"{$user->name}\" direset",
                subject: $user,
                event: 'otp_reset',
                properties: ['otp_dibatalkan' => $invalidated],
                causer: $actor instanceof User ? $actor : null,
            );

            return $user->refresh();
        });
    }
}
