<?php

namespace App\Actions\Pengajuan;

use App\Enums\PengajuanStatus;
use App\Jobs\GroupCompanyJob;
use App\Models\PengajuanSiswa;
use App\Models\User;

/**
 * Gate 2 (F3 alur 3): Walas kelas siswa menandatangani. Job grouping berjalan
 * setelah commit dan mengisi `pengajuan_pkl_id` (TRD.md §10).
 */
final class SignByWalas extends PengajuanAction
{
    public function handle(mixed ...$arguments): int
    {
        /** @var PengajuanSiswa $pengajuan */
        /** @var User $walas */
        [$pengajuan, $walas] = $arguments;

        if ($pengajuan->status === PengajuanStatus::MenungguSuratPengantar->value) {
            return 0;
        }

        $this->assertTransitionable($pengajuan, PengajuanStatus::MenungguSuratPengantar, 'walas');
        $this->authorize('signWalas', $pengajuan, $walas);

        $affected = $this->transaction(function () use ($pengajuan, $walas): int {
            $affected = $this->transition($pengajuan, PengajuanStatus::MenungguSuratPengantar, 'walas');

            if ($affected === 1) {
                $this->audit(
                    $pengajuan,
                    'ditandatangani_walas',
                    PengajuanStatus::MenungguTtdWalas->value,
                    PengajuanStatus::MenungguSuratPengantar->value,
                    ['oleh' => $walas->getKey()],
                );
            }

            return $affected;
        });

        if ($affected === 1) {
            GroupCompanyJob::dispatch((int) $pengajuan->getKey());
        }

        return $affected;
    }
}
