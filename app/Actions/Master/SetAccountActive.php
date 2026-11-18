<?php

namespace App\Actions\Master;

use App\Actions\Action;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Aktifkan atau nonaktifkan akun (T2.2, BR-F1 "akun nonaktif ditolak"). Selain
 * menandai `is_active`, penonaktifan menghapus sesi dan membatalkan OTP akun itu
 * dalam transaksi yang sama, supaya sesi yang masih hidup tidak tetap bisa dipakai.
 */
final class SetAccountActive extends Action
{
    public function handle(mixed ...$arguments): User
    {
        [$user, $active, $actor] = $arguments + [null, true, null];

        if (! $user instanceof User) {
            throw new RuntimeException('Akun tidak dikenal.');
        }

        $active = (bool) $active;

        if ($user->is_active === $active) {
            return $user;
        }

        return $this->transaction(function () use ($user, $active, $actor): User {
            $user->forceFill(['is_active' => $active])->save();

            if (! $active) {
                DB::table('sessions')->where('user_id', $user->getKey())->delete();

                $user->otpCodes()
                    ->whereNull('invalidated_at')
                    ->update(['invalidated_at' => now()]);
            }

            app(AuditService::class)->log(
                description: $active
                    ? "Akun \"{$user->name}\" diaktifkan"
                    : "Akun \"{$user->name}\" dinonaktifkan",
                subject: $user,
                event: $active ? 'account_activated' : 'account_deactivated',
                causer: $actor instanceof User ? $actor : null,
            );

            return $user->refresh();
        });
    }
}
