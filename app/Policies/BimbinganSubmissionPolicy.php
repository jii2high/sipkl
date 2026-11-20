<?php

namespace App\Policies;

use App\Enums\PengajuanStatus;
use App\Enums\Role;
use App\Models\BimbinganSubmission;
use App\Models\PengajuanSiswa;
use App\Models\User;
use App\Services\PlottingService;

/**
 * Otorisasi bimbingan (F6). Siswa hanya mengunggah untuk pengajuannya yang berstatus
 * `sedang_pkl`; guru hanya menilai siswa yang ia bimbing (company+periode plotting).
 */
class BimbinganSubmissionPolicy extends Policy
{
    public function create(User $user, PengajuanSiswa $pengajuan): bool
    {
        return $this->hasActiveRole($user, Role::Siswa->value)
            && (int) $pengajuan->siswa_id === (int) $user->getKey()
            && $pengajuan->status === PengajuanStatus::SedangPkl->value;
    }

    public function view(User $user, BimbinganSubmission $submission): bool
    {
        if ($this->download($user, $submission)) {
            return true;
        }

        return $this->hasActiveRole($user, Role::Hubin->value);
    }

    public function download(User $user, BimbinganSubmission $submission): bool
    {
        $pengajuan = $submission->pengajuanSiswa;

        if ($pengajuan === null) {
            return false;
        }

        if ($this->hasActiveRole($user, Role::Siswa->value)) {
            return (int) $pengajuan->siswa_id === (int) $user->getKey();
        }

        return $this->review($user, $submission);
    }

    public function review(User $user, BimbinganSubmission $submission): bool
    {
        $pengajuan = $submission->pengajuanSiswa;

        return $this->hasActiveRole($user, Role::GuruPembimbing->value)
            && $pengajuan !== null
            && app(PlottingService::class)->isPembimbingOf($user, $pengajuan);
    }

    public function approveFinal(User $user, BimbinganSubmission $submission): bool
    {
        $pengajuan = $submission->pengajuanSiswa;

        return $this->hasActiveRole($user, Role::GuruPembimbing->value)
            && $pengajuan !== null
            && app(PlottingService::class)->isPembimbingOf($user, $pengajuan);
    }
}
