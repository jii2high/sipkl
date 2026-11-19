<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use App\Models\VisitasiDokumentasi;

class VisitasiDokumentasiPolicy extends Policy
{
    public function download(User $user, VisitasiDokumentasi $documentation): bool
    {
        if ($this->hasActiveRole($user, Role::Hubin->value)) {
            return true;
        }

        $visitasi = $documentation->visitasi;

        return match ($this->activeRole($user)) {
            Role::GuruPembimbing->value => (int) $visitasi?->guru_id === (int) $user->getKey(),
            Role::Kaprog->value => (int) $visitasi?->kaprog_id === (int) $user->getKey(),
            default => false,
        };
    }
}
