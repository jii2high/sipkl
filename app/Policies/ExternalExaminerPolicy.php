<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/**
 * Penguji sidang ditetapkan Kaprog untuk program keahliannya sendiri (PD-16).
 */
class ExternalExaminerPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Kaprog->value);
    }

    public function create(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Kaprog->value)
            && $user->roleScopeId(Role::Kaprog) !== null;
    }
}
