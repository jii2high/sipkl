<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\JadwalSidang;
use App\Models\PengajuanSiswa;
use App\Models\User;

/**
 * Penjadwalan sidang (F9) hanya untuk Kaprog program keahlian siswa tersebut.
 */
class JadwalSidangPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Kaprog->value);
    }

    public function schedule(User $user, PengajuanSiswa $pengajuan): bool
    {
        return $this->inJurusan($user, $pengajuan->siswa?->jurusan_id);
    }

    public function shift(User $user, JadwalSidang $jadwal): bool
    {
        return $this->inJurusan($user, $jadwal->pengajuanSiswa?->siswa?->jurusan_id);
    }

    public function issueLink(User $user, JadwalSidang $jadwal): bool
    {
        return $this->shift($user, $jadwal);
    }

    private function inJurusan(User $user, mixed $jurusanId): bool
    {
        $scope = $user->roleScopeId(Role::Kaprog);

        return $this->hasActiveRole($user, Role::Kaprog->value)
            && $scope !== null
            && $jurusanId !== null
            && (int) $jurusanId === $scope;
    }
}
