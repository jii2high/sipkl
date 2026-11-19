<?php

namespace App\Policies;

use App\Enums\PengajuanStatus;
use App\Enums\Role;
use App\Models\PengajuanSiswa;
use App\Models\User;

/**
 * Otorisasi pengajuan siswa (TRD.md §7.3): peran aktif + scope objek + status objek.
 * Siswa hanya pengajuannya sendiri; Kaprog hanya jurusannya; Walas hanya kelasnya.
 */
class PengajuanSiswaPolicy extends Policy
{
    public function viewAny(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Siswa->value, Role::Kaprog->value, Role::Walas->value);
    }

    public function view(User $user, PengajuanSiswa $pengajuan): bool
    {
        return match ($this->activeRole($user)) {
            Role::Siswa->value => (int) $pengajuan->siswa_id === (int) $user->getKey(),
            Role::Kaprog->value => $this->inJurusan($user, $pengajuan),
            Role::Walas->value => $this->inKelas($user, $pengajuan),
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return $this->hasActiveRole($user, Role::Siswa->value);
    }

    public function update(User $user, PengajuanSiswa $pengajuan): bool
    {
        return $this->owns($user, $pengajuan)
            && $pengajuan->status === PengajuanStatus::Draft->value;
    }

    public function submit(User $user, PengajuanSiswa $pengajuan): bool
    {
        return $this->owns($user, $pengajuan)
            && $pengajuan->status === PengajuanStatus::Draft->value;
    }

    public function reopen(User $user, PengajuanSiswa $pengajuan): bool
    {
        return $this->owns($user, $pengajuan)
            && in_array($pengajuan->status, [
                PengajuanStatus::DitolakKaprog->value,
                PengajuanStatus::DitolakWalas->value,
            ], true);
    }

    public function approveKaprog(User $user, PengajuanSiswa $pengajuan): bool
    {
        return $this->hasActiveRole($user, Role::Kaprog->value)
            && $this->inJurusan($user, $pengajuan)
            && $pengajuan->status === PengajuanStatus::MenungguVerifikasiKaprog->value;
    }

    public function rejectKaprog(User $user, PengajuanSiswa $pengajuan): bool
    {
        return $this->approveKaprog($user, $pengajuan);
    }

    public function signWalas(User $user, PengajuanSiswa $pengajuan): bool
    {
        return $this->hasActiveRole($user, Role::Walas->value)
            && $this->inKelas($user, $pengajuan)
            && $pengajuan->status === PengajuanStatus::MenungguTtdWalas->value;
    }

    public function rejectWalas(User $user, PengajuanSiswa $pengajuan): bool
    {
        return $this->signWalas($user, $pengajuan);
    }

    public function delete(User $user, PengajuanSiswa $pengajuan): bool
    {
        return false;
    }

    public function uploadSuratBalasan(User $user, PengajuanSiswa $pengajuan): bool
    {
        return $this->owns($user, $pengajuan)
            && $pengajuan->status === PengajuanStatus::SuratPengantarTerbit->value;
    }

    public function acceptByCompany(User $user, PengajuanSiswa $pengajuan): bool
    {
        return $this->hasActiveRole($user, Role::Kaprog->value)
            && $this->inJurusan($user, $pengajuan)
            && $pengajuan->status === PengajuanStatus::MenungguBalasanPerusahaan->value;
    }

    public function rejectByCompany(User $user, PengajuanSiswa $pengajuan): bool
    {
        return $this->acceptByCompany($user, $pengajuan);
    }

    public function confirmPhysicalDocument(User $user, PengajuanSiswa $pengajuan): bool
    {
        return $this->hasActiveRole($user, Role::Hubin->value)
            && $pengajuan->status === PengajuanStatus::SedangPkl->value;
    }

    public function requestWithdrawal(User $user, PengajuanSiswa $pengajuan): bool
    {
        if ($pengajuan->status !== PengajuanStatus::SedangPkl->value) {
            return false;
        }

        return match ($this->activeRole($user)) {
            Role::Siswa->value => (int) $pengajuan->siswa_id === (int) $user->getKey(),
            Role::Kaprog->value => $this->inJurusan($user, $pengajuan),
            default => false,
        };
    }

    public function approveWithdrawal(User $user, PengajuanSiswa $pengajuan): bool
    {
        return $this->hasActiveRole($user, Role::Kaprog->value)
            && $this->inJurusan($user, $pengajuan)
            && $pengajuan->status === PengajuanStatus::SedangPkl->value
            && $pengajuan->withdraw_requested_at !== null;
    }

    private function owns(User $user, PengajuanSiswa $pengajuan): bool
    {
        return $this->hasActiveRole($user, Role::Siswa->value)
            && (int) $pengajuan->siswa_id === (int) $user->getKey();
    }

    private function inJurusan(User $user, PengajuanSiswa $pengajuan): bool
    {
        $scope = $user->roleScopeId(Role::Kaprog);

        return $scope !== null && (int) $pengajuan->siswa?->jurusan_id === $scope;
    }

    private function inKelas(User $user, PengajuanSiswa $pengajuan): bool
    {
        $scope = $user->roleScopeId(Role::Walas);

        return $scope !== null && (int) $pengajuan->siswa?->kelas_id === $scope;
    }
}
