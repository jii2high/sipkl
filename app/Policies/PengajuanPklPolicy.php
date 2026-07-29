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
            && in_array($pengajuan->status->getValue(), ['draft', 'ditolak']);
    }

    public function approve(User $user, PengajuanPkl $pengajuan): bool
    {
        return $user->hasPermissionTo('pengajuan.setujui')
            && $user->guruProfile?->kompetensi_keahlian_id === $pengajuan->siswa->kompetensi_keahlian_id
            && $pengajuan->status->getValue() === 'diajukan';
    }

    public function reject(User $user, PengajuanPkl $pengajuan): bool
    {
        return $user->hasPermissionTo('pengajuan.tolak')
            && $user->guruProfile?->kompetensi_keahlian_id === $pengajuan->siswa->kompetensi_keahlian_id
            && $pengajuan->status->getValue() === 'diajukan';
    }
}
