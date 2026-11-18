<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/**
 * Otorisasi pengelolaan akun (T2.2). Hubin mengelola akun; akun tidak pernah
 * dihapus (jejak akademik), hanya dinonaktifkan lewat `SetAccountActive`.
 */
class UserPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    public function view(User $user, User $account): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    public function create(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    public function update(User $user, User $account): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    /** Reset akses OTP akun lain (T2.2). */
    public function resetOtp(User $user, User $account): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    /** Aktifkan/nonaktifkan akun (T2.2, BR-F1). */
    public function toggleActive(User $user, User $account): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    public function import(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    public function delete(User $user, User $account): bool
    {
        return false;
    }
}
