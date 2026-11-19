<?php

namespace App\Actions\Pengajuan;

use App\Enums\PengajuanStatus;
use App\Models\PengajuanSiswa;
use App\Models\User;

/**
 * Gate 2 ditolak (BR-F3-2). Alasan wajib ≥ 10 karakter dan tetap terbaca di audit.
 */
final class RejectByWalas extends PengajuanAction
{
    public function handle(mixed ...$arguments): int
    {
        /** @var PengajuanSiswa $pengajuan */
        /** @var User $walas */
        /** @var string|null $note */
        [$pengajuan, $walas, $note] = $arguments + [null, null, null];

        if ($pengajuan->status === PengajuanStatus::DitolakWalas->value) {
            return 0;
        }

        $this->assertTransitionable($pengajuan, PengajuanStatus::DitolakWalas, 'walas');
        $this->authorize('rejectWalas', $pengajuan, $walas);

        $note = $this->assertRejectionNote($note);

        return $this->transaction(function () use ($pengajuan, $note, $walas): int {
            $affected = $this->transition($pengajuan, PengajuanStatus::DitolakWalas, 'walas');

            if ($affected === 1) {
                $this->audit(
                    $pengajuan,
                    'ditolak_walas',
                    PengajuanStatus::MenungguTtdWalas->value,
                    PengajuanStatus::DitolakWalas->value,
                    [
                        'catatan' => $note,
                        'oleh' => $walas->getKey(),
                    ],
                );
            }

            return $affected;
        });
    }
}
