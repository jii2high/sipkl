<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ScopesByActiveRole;

/**
 * Otorisasi master data pengguna (TASKS M2.7).
 *
 * Hanya `active_role=hubin` yang boleh mengelola akun dan peran. Akun tidak
 * dihapus lewat UI karena jejak pengajuan/nilai nanti menunjuk ke `users.id`.
 */
class UserPolicy
{
    use ScopesByActiveRole;

    public function viewAny(User $user): bool
    {
        return $this->activeRole()->is('hubin');
    }

    public function view(User $user, User $target): bool
    {
        return $this->activeRole()->is('hubin');
    }

    public function create(User $user): bool
    {
        return $this->activeRole()->is('hubin');
    }

    public function update(User $user, User $target): bool
    {
        return $this->activeRole()->is('hubin');
    }

    public function delete(User $user, User $target): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
