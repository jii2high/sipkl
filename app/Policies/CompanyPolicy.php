<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Company;
use App\Models\User;

/**
 * Otorisasi master data perusahaan (TRD.md §7.3). Pengelolaan dan merge hanya
 * Hubin; peran lain baru mendapat akses baca pada task yang membutuhkannya (T4.1).
 */
class CompanyPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    public function view(User $user, Company $company): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    public function create(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    public function update(User $user, Company $company): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    /** Merge memindahkan referensi; hanya Hubin yang boleh memicunya (BR-F2-4). */
    public function merge(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    public function import(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    public function delete(User $user, Company $company): bool
    {
        return false;
    }
}
