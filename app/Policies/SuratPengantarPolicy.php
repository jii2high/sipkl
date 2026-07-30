<?php

namespace App\Policies;

use App\Models\PengajuanPkl;
use App\Models\SuratPengantar;
use App\Models\User;

class SuratPengantarPolicy
{
    /**
     * Hanya Hubin yang bisa melihat daftar surat pengantar.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('hubin');
    }

    /**
     * Hubin dan siswa pemilik pengajuan bisa lihat surat pengantar.
     */
    public function view(User $user, SuratPengantar $suratPengantar): bool
    {
        if ($user->hasRole('hubin')) {
            return true;
        }

        // Siswa hanya bisa lihat surat yang memuat pengajuannya
        if ($user->hasRole('siswa') && $user->siswaProfile) {
            return $suratPengantar->pengajuans()
                ->where('siswa_id', $user->siswaProfile->id)
                ->exists();
        }

        return false;
    }

    /**
     * Hanya Hubin yang bisa menerbitkan (kirim) surat pengantar.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('hubin');
    }

    /**
     * Izin upload surat balasan — hanya siswa pemilik pengajuan berstatus disetujui.
     * Dipakai dari PengajuanPklPolicy::uploadBalasan().
     */
    public function uploadBalasan(User $user, PengajuanPkl $pengajuan): bool
    {
        if (! $user->hasRole('siswa') || ! $user->siswaProfile) {
            return false;
        }

        // Pengajuan milik siswa ini dan status disetujui
        return $pengajuan->siswa_id === $user->siswaProfile->id
            && $pengajuan->status === 'disetujui';
    }

    /**
     * Hubin bisa tolak balasan pasca-fakta pada pengajuan berstatus resmi_pkl.
     * Dipakai via PengajuanPklPolicy::tolakBalasan().
     */
    public function tolakBalasan(User $user, PengajuanPkl $pengajuan): bool
    {
        return $user->hasRole('hubin') && $pengajuan->status === 'resmi_pkl';
    }

    // Tidak ada update / delete langsung pada surat pengantar
    public function update(User $user, SuratPengantar $suratPengantar): bool
    {
        return false;
    }

    public function delete(User $user, SuratPengantar $suratPengantar): bool
    {
        return false;
    }
}
