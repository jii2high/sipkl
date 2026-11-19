<?php

namespace App\Actions\Pengajuan;

use App\Enums\PengajuanStatus;
use App\Models\PengajuanSiswa;
use App\Models\User;

/**
 * Buka kembali pengajuan yang ditolak agar siswa dapat memperbaiki (TRD.md §5.2).
 * Snapshot lama dibiarkan; submit berikutnya membekukan snapshot baru.
 */
final class ReopenPengajuan extends PengajuanAction
{
    public function handle(mixed ...$arguments): int
    {
        /** @var PengajuanSiswa $pengajuan */
        /** @var User $siswa */
        [$pengajuan, $siswa] = $arguments;

        if ($pengajuan->status === PengajuanStatus::Draft->value) {
            return 0;
        }

        $this->assertTransitionable($pengajuan, PengajuanStatus::Draft, 'siswa');
        $this->authorize('reopen', $pengajuan, $siswa);

        return $this->transaction(function () use ($pengajuan): int {
            $from = (string) $pengajuan->status;

            $affected = $this->transition($pengajuan, PengajuanStatus::Draft, 'siswa');

            if ($affected === 1) {
                $this->audit($pengajuan, 'pengajuan_dibuka_kembali', $from, PengajuanStatus::Draft->value);
            }

            return $affected;
        });
    }
}
