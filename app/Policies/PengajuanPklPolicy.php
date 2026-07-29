<?php

namespace App\Policies;

use App\Models\PengajuanPkl;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class PengajuanPklPolicy
{
    public function view(User $user, PengajuanPkl $pengajuan): bool
    {
        if ($user->hasRole('kaprog')) {
            return $pengajuan->siswa?->kompetensi_keahlian_id === $user->guruProfile?->kompetensi_keahlian_id;
        }

        return $pengajuan->siswa_id === $user->siswaProfile?->id;
    }

    public function verifikasi(User $user, PengajuanPkl $pengajuan): bool
    {
        return $user->hasRole('kaprog')
            && $pengajuan->siswa?->kompetensi_keahlian_id === $user->guruProfile?->kompetensi_keahlian_id;
    }

    /** @param Builder<PengajuanPkl> $query */
    public function scopeUntukKaprog(Builder $query, User $user): void
    {
        $query->whereHas('siswa', fn ($q) => $q->where('kompetensi_keahlian_id', $user->guruProfile?->kompetensi_keahlian_id));
    }
}
