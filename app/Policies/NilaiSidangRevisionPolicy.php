<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\NilaiSidangRevision;
use App\Models\User;

/**
 * Hanya Kaprog program keahlian siswa yang boleh menyetujui permintaan revisi
 * nilai dari penguji (F9 alur 5).
 */
class NilaiSidangRevisionPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Kaprog->value);
    }

    public function approve(User $user, NilaiSidangRevision $revision): bool
    {
        $scope = $user->roleScopeId(Role::Kaprog);
        $jurusanId = $revision->submission?->pengajuanSiswa?->siswa?->jurusan_id;

        return $this->hasActiveRole($user, Role::Kaprog->value)
            && $scope !== null
            && $jurusanId !== null
            && (int) $jurusanId === $scope;
    }
}
