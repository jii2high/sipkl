<?php

namespace App\Actions\Pengajuan;

use App\Enums\PengajuanStatus;
use App\Models\PengajuanSiswa;
use App\Models\User;

/**
 * Kaprog menolak siswa setelah balasan perusahaan (F3 alur 6). Hanya siswa itu
 * yang ditolak; slot tidak pernah terpakai sehingga tidak perlu dilepas.
 */
final class RejectByCompany extends PengajuanAction
{
    public function handle(mixed ...$arguments): int
    {
        /** @var PengajuanSiswa $pengajuan */
        /** @var User $kaprog */
        [$pengajuan, $kaprog, $note] = $arguments + [null, null, null];

        if ($pengajuan->status === PengajuanStatus::Rejected->value) {
            return 0;
        }

        $this->assertTransitionable($pengajuan, PengajuanStatus::Rejected, 'kaprog');
        $this->authorize('rejectByCompany', $pengajuan, $kaprog);

        $note = is_string($note) && trim($note) !== '' ? trim($note) : null;

        return $this->transaction(function () use ($pengajuan, $kaprog, $note): int {
            $affected = $this->transition($pengajuan, PengajuanStatus::Rejected, 'kaprog');

            if ($affected === 1) {
                $this->audit(
                    $pengajuan,
                    'ditolak_perusahaan',
                    PengajuanStatus::MenungguBalasanPerusahaan->value,
                    PengajuanStatus::Rejected->value,
                    ['catatan' => $note, 'oleh' => $kaprog->getKey()],
                );
            }

            return $affected;
        });
    }
}
