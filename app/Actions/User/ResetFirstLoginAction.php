<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Reset alur login awal (TASKS M2.7).
 *
 * Password dikembalikan ke NIS/NIP dan `is_first_login` diaktifkan lagi
 * sehingga user wajib melewati OTP + ganti password pada login berikutnya.
 * Password tidak pernah ditulis ke audit log.
 */
final class ResetFirstLoginAction
{
    public function execute(User $user, ?User $oleh = null): User
    {
        return DB::transaction(function () use ($user, $oleh): User {
            /** @var User $user */
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());

            $user->forceFill([
                'password' => $user->nis_nip,
                'is_first_login' => true,
            ])->save();

            activity('user')
                ->performedOn($user)
                ->causedBy($oleh)
                ->withProperties(['aksi' => 'reset_first_login'])
                ->log('user.first_login_direset');

            return $user;
        });
    }
}
