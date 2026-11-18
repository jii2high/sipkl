<?php

namespace App\Policies;

use App\Models\User;

/**
 * Dasar semua Policy (TRD.md §7.3): setiap keputusan memeriksa peran aktif. Peran
 * aktif disimpan di sesi dan diisi `SwitchActiveRole` (M1). Policy konkret menambah
 * pemeriksaan scope objek dan status objek lewat trait/model terkait.
 */
abstract class Policy
{
    /** Peran aktif pada request berjalan, atau null bila belum ada. */
    protected function activeRole(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        $role = session('active_role');

        return is_string($role) ? $role : null;
    }

    /** User punya salah satu peran aktif yang diminta? */
    protected function hasActiveRole(?User $user, string ...$roles): bool
    {
        $active = $this->activeRole($user);

        return $active !== null && in_array($active, $roles, true);
    }
}
