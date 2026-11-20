<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\FormSubmission;
use App\Models\User;

/**
 * Nilai sidang yang sudah dikunci hanya dapat dibaca/diberi kelulusan oleh Kaprog
 * program keahlian siswa (F9, Q-I).
 */
class FormSubmissionPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Kaprog->value);
    }

    public function setKelulusan(User $user, FormSubmission $submission): bool
    {
        $scope = $user->roleScopeId(Role::Kaprog);
        $jurusanId = $submission->pengajuanSiswa?->siswa?->jurusan_id;

        return $this->hasActiveRole($user, Role::Kaprog->value)
            && $scope !== null
            && $jurusanId !== null
            && (int) $jurusanId === $scope;
    }
}
