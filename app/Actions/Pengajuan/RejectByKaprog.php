<?php

namespace App\Actions\Pengajuan;

use App\Enums\PengajuanStatus;
use App\Models\PengajuanSiswa;
use App\Models\User;

/**
 * Gate 1 ditolak (BR-F3-2). Alasan wajib ≥ 10 karakter dan tersimpan di audit;
 * siswa dapat memperbaiki lalu mengirim ulang.
 */
final class RejectByKaprog extends PengajuanAction
{
    public function handle(mixed ...$arguments): int
    {
        /** @var PengajuanSiswa $pengajuan */
        /** @var User $kaprog */
        /** @var string|null $note */
        [$pengajuan, $kaprog, $note] = $arguments + [null, null, null];

        if ($pengajuan->status === PengajuanStatus::DitolakKaprog->value) {
            return 0;
        }

        $this->assertTransitionable($pengajuan, PengajuanStatus::DitolakKaprog, 'kaprog');
        $this->authorize('rejectKaprog', $pengajuan, $kaprog);

        $note = $this->assertRejectionNote($note);

        return $this->transaction(function () use ($pengajuan, $note, $kaprog): int {
            $affected = $this->transition($pengajuan, PengajuanStatus::DitolakKaprog, 'kaprog');

            if ($affected === 1) {
                $this->audit(
                    $pengajuan,
                    'ditolak_kaprog',
                    PengajuanStatus::MenungguVerifikasiKaprog->value,
                    PengajuanStatus::DitolakKaprog->value,
                    [
                        'catatan' => $note,
                        'oleh' => $kaprog->getKey(),
                    ],
                );
            }

            return $affected;
        });
    }
}
