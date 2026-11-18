<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use App\Policies\Concerns\ScopesByActiveRole;

/**
 * Otorisasi master data perusahaan (TASKS M2.4).
 *
 * Hanya `active_role=hubin` yang boleh mengelola perusahaan; role lain selalu
 * ditolak walaupun UI-nya kebetulan menampilkan tombol (PRD §1.5 butir 5).
 */
class CompanyPolicy
{
    use ScopesByActiveRole;

    public function viewAny(User $user): bool
    {
        return $this->activeRole()->is('hubin');
    }

    public function view(User $user, Company $company): bool
    {
        return $this->activeRole()->is('hubin');
    }

    public function create(User $user): bool
    {
        return $this->activeRole()->is('hubin');
    }

    public function update(User $user, Company $company): bool
    {
        return $this->activeRole()->is('hubin');
    }

    public function delete(User $user, Company $company): bool
    {
        return $this->activeRole()->is('hubin');
    }

    public function deleteAny(User $user): bool
    {
        return $this->activeRole()->is('hubin');
    }
}
