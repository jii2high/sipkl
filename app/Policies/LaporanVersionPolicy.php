<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\LaporanVersion;
use App\Models\User;
use App\Services\PlottingService;

/**
 * Akses berkas laporan final (F7): semua pemangku rantai TTD boleh membaca versi
 * yang sedang/ sudah ditandatangani; di luar itu hanya pemilik pengajuan.
 */
class LaporanVersionPolicy extends Policy
{
    public function download(User $user, LaporanVersion $version): bool
    {
        $pengajuan = $version->pengajuanSiswa;

        if ($pengajuan === null) {
            return false;
        }

        return match ($this->activeRole($user)) {
            Role::Hubin->value, Role::Kepsek->value => true,
            Role::Kaprog->value => (int) $pengajuan->siswa?->jurusan_id === (int) $user->roleScopeId(Role::Kaprog),
            Role::GuruPembimbing->value => app(PlottingService::class)->isPembimbingOf($user, $pengajuan),
            Role::Siswa->value => (int) $pengajuan->siswa_id === (int) $user->getKey(),
            default => false,
        };
    }
}
