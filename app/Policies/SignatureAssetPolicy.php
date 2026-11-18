<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

/**
 * Otorisasi scan TTD Kepsek (BR-F2-5). Hanya Hubin yang boleh melihat dan
 * mengunggah; file sendiri disimpan privat di disk `local`.
 */
class SignatureAssetPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    public function create(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value);
    }
}
