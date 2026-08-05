<?php

namespace App\Policies;

use App\Models\PengajuanPkl;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PengajuanPklPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['pengajuan.lihat', 'pengajuan.lihat-sendiri']);
    }

    public function view(User $user, PengajuanPkl $pengajuan): bool
    {
        if ($user->hasPermissionTo('pengajuan.lihat-sendiri') && $user->siswaProfile?->id === $pengajuan->siswa_id) {
            return true;
        }

        if ($user->hasPermissionTo('pengajuan.lihat') && $user->guruProfile?->kompetensi_keahlian_id === $pengajuan->siswa->kompetensi_keahlian_id) {
            return true;
        }

        if ($user->hasPermissionTo('pengajuan.lihat') && $user->hasRole('hubin')) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        if (! $user->hasPermissionTo('pengajuan.ajukan')) {
            return false;
        }

        return ! PengajuanPkl::where('siswa_id', $user->siswaProfile?->id)
            ->whereHas('periode', fn ($q) => $q->where('status_aktif', true))
            ->whereNotIn('status', ['ditolak'])
            ->exists();
    }

    public function submit(User $user, PengajuanPkl $pengajuan): bool
    {
        return $user->hasPermissionTo('pengajuan.ajukan')
            && $user->siswaProfile?->id === $pengajuan->siswa_id
            && in_array($pengajuan->status, ['draft', 'ditolak']);
    }

    public function approve(User $user, PengajuanPkl $pengajuan): bool
    {
        return $user->hasPermissionTo('pengajuan.setujui')
            && $user->guruProfile?->kompetensi_keahlian_id === $pengajuan->siswa->kompetensi_keahlian_id
            && $pengajuan->status === 'diajukan';
    }

    public function reject(User $user, PengajuanPkl $pengajuan): bool
    {
        return $user->hasPermissionTo('pengajuan.tolak')
            && $user->guruProfile?->kompetensi_keahlian_id === $pengajuan->siswa->kompetensi_keahlian_id
            && $pengajuan->status === 'diajukan';
    }

    /**
     * Kaprog bisa assign Guru Pembimbing — eksklusif (BR10) dan cuma siswa jurusannya sendiri.
     */
    public function assign(User $user, PengajuanPkl $pengajuan): bool
    {
        return $user->hasPermissionTo('penugasan.kelola')
            && $user->guruProfile?->kompetensi_keahlian_id === $pengajuan->siswa->kompetensi_keahlian_id;
    }

    /**
     * Siswa bisa upload surat balasan (BR20): hanya jika ada surat pengantar
     * untuk pengajuan ini (status 'disetujui', surat pengantar sudah diterbitkan).
     */
    public function uploadBalasan(User $user, PengajuanPkl $pengajuan): bool
    {
        if (! $user->hasRole('siswa') || ! $user->siswaProfile) {
            return false;
        }

        return $pengajuan->siswa_id === $user->siswaProfile->id
            && $pengajuan->status === 'disetujui'
            && $pengajuan->suratPengantars()->exists();
    }

    /**
     * Siswa melihat pengajuan miliknya saja.
     */
    public function lihatSendiri(User $user, PengajuanPkl $pengajuan): bool
    {
        return $user->hasRole('siswa')
            && $user->siswaProfile?->id === $pengajuan->siswa_id;
    }

    /**
     * Hubin bisa tolak surat balasan pasca-fakta pada pengajuan berstatus resmi_pkl.
     */
    public function tolakBalasan(User $user, PengajuanPkl $pengajuan): bool
    {
        return $user->hasRole('hubin') && $pengajuan->status === 'resmi_pkl';
    }
}
