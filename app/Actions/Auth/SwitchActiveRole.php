<?php

namespace App\Actions\Auth;

use App\Actions\Action;
use App\Enums\Role;
use App\Models\User;

/**
 * Pindah peran aktif (TRD.md §7.2). Menolak 403 bila pengguna tidak memegang peran
 * itu (BR-F1-5), mengganti ID sesi, dan membersihkan state per peran sebelumnya
 * supaya data peran lama tidak terbawa (KP-F1-5).
 */
final class SwitchActiveRole extends Action
{
    /** Awalan kunci sesi yang hanya berlaku untuk satu peran aktif. */
    public const ROLE_SCOPED_PREFIX = 'role_';

    public function handle(mixed ...$arguments): string
    {
        [$user, $role] = $arguments;
        $role = $role instanceof Role ? $role->value : (string) $role;

        abort_unless($user instanceof User && $user->hasRole($role), 403, __('pkl.role.not_owned'));

        $session = session();

        foreach (array_keys($session->all()) as $key) {
            if (is_string($key) && str_starts_with($key, self::ROLE_SCOPED_PREFIX)) {
                $session->forget($key);
            }
        }

        // Regenerasi ID sesi saat pindah peran (TRD.md §16) lalu simpan peran baru.
        $session->regenerate();
        $session->put('active_role', $role);

        return $role;
    }
}
