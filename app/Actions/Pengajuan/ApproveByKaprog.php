<?php

namespace App\Actions\Pengajuan;

use App\Enums\PengajuanStatus;
use App\Models\PengajuanSiswa;
use App\Models\User;

/**
 * Gate 1 (F3 alur 3): Kaprog jurusan siswa menyetujui relevansi. Belum memakai
 * kuota; kuota baru dihitung saat penerimaan perusahaan (T4.2).
 */
final class ApproveByKaprog extends PengajuanAction
{
    public function handle(mixed ...$arguments): int
    {
        /** @var PengajuanSiswa $pengajuan */
        /** @var User $kaprog */
        [$pengajuan, $kaprog] = $arguments;

        if ($pengajuan->status === PengajuanStatus::MenungguTtdWalas->value) {
            return 0;
        }

        $this->assertTransitionable($pengajuan, PengajuanStatus::MenungguTtdWalas, 'kaprog');
        $this->authorize('approveKaprog', $pengajuan, $kaprog);

        return $this->transaction(function () use ($pengajuan): int {
            $affected = $this->transition($pengajuan, PengajuanStatus::MenungguTtdWalas, 'kaprog');

            if ($affected === 1) {
                $this->audit(
                    $pengajuan,
                    'disetujui_kaprog',
                    PengajuanStatus::MenungguVerifikasiKaprog->value,
                    PengajuanStatus::MenungguTtdWalas->value,
                );
            }

            return $affected;
        });
    }
}
