<?php

namespace App\Actions\Sidang;

use App\Actions\Action;
use App\Models\ExternalExaminer;
use App\Models\JadwalSidang;
use App\Models\PengajuanSiswa;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Kaprog menjadwalkan siswa dari pool Siap Sidang dan menetapkan penguji (F9 alur 1).
 * Hanya siswa dengan laporan `is_legit` yang boleh dijadwalkan (KP-F9-5); satu siswa
 * satu baris jadwal (`jadwal_sidang.pengajuan_siswa_id` UNIQUE), perpindahan lewat
 * ShiftSidang.
 */
final class ScheduleSidang extends Action
{
    public function handle(mixed ...$arguments): JadwalSidang
    {
        [$pengajuan, $examiner, $tanggal, $kaprog] = $arguments;

        if (! $pengajuan instanceof PengajuanSiswa || ! $examiner instanceof ExternalExaminer
            || ! $kaprog instanceof User || ! is_string($tanggal)) {
            throw new RuntimeException('Pengajuan, penguji, tanggal, dan Kaprog wajib diisi.');
        }

        Gate::forUser($kaprog)->authorize('schedule', [JadwalSidang::class, $pengajuan]);

        return $this->transaction(function () use ($pengajuan, $examiner, $tanggal, $kaprog): JadwalSidang {
            $locked = PengajuanSiswa::query()->whereKey($pengajuan->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->hasLegitReport()) {
                throw ValidationException::withMessages(['pengajuanId' => __('pkl.sidang.not_legit')]);
            }

            if (JadwalSidang::query()->where('pengajuan_siswa_id', $locked->getKey())->exists()) {
                throw ValidationException::withMessages(['pengajuanId' => __('pkl.sidang.already_scheduled')]);
            }

            $this->assertWithinPkl($locked, $tanggal);

            $urutan = (int) JadwalSidang::query()->where('tanggal', $tanggal)->max('urutan') + 1;

            $jadwal = JadwalSidang::query()->create([
                'pengajuan_siswa_id' => $locked->getKey(),
                'external_examiner_id' => $examiner->getKey(),
                'tanggal' => $tanggal,
                'urutan' => $urutan,
                'status' => JadwalSidang::STATUS_TERJADWAL,
                'scheduled_by' => $kaprog->getKey(),
            ]);

            $this->audit(
                $jadwal,
                'sidang_dijadwalkan',
                null,
                JadwalSidang::STATUS_TERJADWAL,
                [
                    'pengajuan_siswa_id' => $locked->getKey(),
                    'external_examiner_id' => $examiner->getKey(),
                    'tanggal' => $tanggal,
                    'urutan' => $urutan,
                ],
                'Sidang dijadwalkan',
            );

            return $jadwal;
        });
    }

    private function assertWithinPkl(PengajuanSiswa $pengajuan, string $tanggal): void
    {
        $selesai = $pengajuan->tanggal_selesai_pkl?->toDateString();

        if ($selesai !== null && $tanggal > $selesai) {
            throw ValidationException::withMessages([
                'tanggal' => __('pkl.sidang.beyond_period', ['tanggal' => $selesai]),
            ]);
        }
    }
}
