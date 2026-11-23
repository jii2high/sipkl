<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\FormDefinition;
use App\Models\User;

/**
 * Form builder (F8) hanya untuk Kaprog, dibatasi program keahliannya sendiri
 * (TRD.md §7.3).
 */
class FormDefinitionPolicy extends Policy
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

    public function view(User $user, FormDefinition $definition): bool
    {
        return $this->sameJurusan($user, $definition);
    }

    public function update(User $user, FormDefinition $definition): bool
    {
        return $this->sameJurusan($user, $definition);
    }

    public function publish(User $user, FormDefinition $definition): bool
    {
        return $this->sameJurusan($user, $definition);
    }

    private function sameJurusan(User $user, FormDefinition $definition): bool
    {
        return $this->hasActiveRole($user, Role::Kaprog->value)
            && (int) $definition->jurusan_id === (int) $user->roleScopeId(Role::Kaprog);
    }
}
