<?php

namespace App\Policies;

use App\Models\PenugasanPembimbing;
use App\Models\SuratTugasMonitoring;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Builder;

class KunjunganMonitoringPolicy
{
    use HandlesAuthorization;

    /**
     * Guru pembimbing (atau Kaprog yang self-assign) boleh isi laporan kunjungan
     * hanya jika ada Surat Tugas Monitoring aktif yang meng-cover periode & jurusannya (BR22),
     * dan masih berlaku sampai tanggal kunjungan.
     */
    public function create(User $user, PenugasanPembimbing $penugasan): bool
    {
        if ($penugasan->status !== 'aktif') {
            return false;
        }

        if ($user->id !== $penugasan->guru_pembimbing_id) {
            return false;
        }

        if (! $user->hasPermissionTo('kunjungan.isi')) {
            return false;
        }

        return SuratTugasMonitoring::query()
            ->where('periode_id', $penugasan->pengajuan->periode_id)
            ->where('status', 'aktif')
            ->where(function (Builder $q) use ($penugasan) {
                $q->where('lingkup', 'global')
                    ->orWhere('kompetensi_keahlian_id', $penugasan->pengajuan->siswa->kompetensi_keahlian_id);
            })
            ->where(function (Builder $q) {
                $q->whereNull('berlaku_hingga')
                    ->orWhereDate('berlaku_hingga', '>=', today());
            })
            ->exists();
    }
}
