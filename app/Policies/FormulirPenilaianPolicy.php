<?php

namespace App\Policies;

use App\Models\FormulirPenilaian;
use App\Models\PenugasanPembimbing;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FormulirPenilaianPolicy
{
    use HandlesAuthorization;

    /**
     * BR11 + BR32: hanya pembimbing aktif dari siswa tsb, dan gate Laporan Akhir
     * harus sudah terbuka (laporan_akhir_uploaded_at terisi).
     */
    public function create(User $user, PenugasanPembimbing $penugasan): bool
    {
        if ($penugasan->status !== 'aktif') {
            return false;
        }

        if ($user->id !== $penugasan->guru_pembimbing_id) {
            return false;
        }

        if (! $user->hasPermissionTo('penilaian.isi')) {
            return false;
        }

        // BR32: form belum boleh diakses sebelum Laporan Akhir diupload siswa.
        if ($penugasan->pengajuan->laporan_akhir_uploaded_at === null) {
            return false;
        }

        // Sekali isi (BR31): formulir yang sudah submitted bersifat final.
        return ! FormulirPenilaian::where('penugasan_id', $penugasan->id)->whereNotNull('submitted_at')->exists();
    }

    /**
     * Melihat formulir yang sudah ada — pembimbing dari penugasan tsb (BR11).
     */
    public function view(User $user, FormulirPenilaian $formulir): bool
    {
        return $user->id === $formulir->guru_pembimbing_id;
    }
}
