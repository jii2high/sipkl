<?php

namespace App\Actions\Pengajuan;

use App\Enums\PengajuanStatus;
use App\Models\BimbinganSubmission;
use App\Models\PengajuanSiswa;
use App\Models\User;

/**
 * Kaprog menyetujui penarikan (F3 alur 9). Status menjadi `withdrawn` sehingga
 * slot kuota langsung lepas; hook pengarsipan bimbingan disediakan untuk T6.1.
 */
final class ApproveWithdrawal extends PengajuanAction
{
    public function handle(mixed ...$arguments): int
    {
        /** @var PengajuanSiswa $pengajuan */
        /** @var User $kaprog */
        [$pengajuan, $kaprog] = $arguments;

        if ($pengajuan->status === PengajuanStatus::Withdrawn->value) {
            return 0;
        }

        $this->assertTransitionable($pengajuan, PengajuanStatus::Withdrawn, 'kaprog');
        $this->authorize('approveWithdrawal', $pengajuan, $kaprog);

        return $this->transaction(function () use ($pengajuan, $kaprog): int {
            if ($pengajuan->withdraw_requested_at === null) {
                return 0;
            }

            $affected = $this->transition(
                $pengajuan,
                PengajuanStatus::Withdrawn,
                'kaprog',
                [
                    'withdraw_approved_by' => $kaprog->getKey(),
                    'withdrawn_at' => now(),
                ],
            );

            if ($affected === 1) {
                $this->audit(
                    $pengajuan,
                    'penarikan_disetujui',
                    PengajuanStatus::SedangPkl->value,
                    PengajuanStatus::Withdrawn->value,
                    [
                        'alasan' => $pengajuan->withdraw_reason,
                        'oleh' => $kaprog->getKey(),
                    ],
                );

                // T6.1: arsipkan histori bimbingan siswa; unggahan berikutnya memakai
                // record baru dan hitungan interaksi mulai dari 0 (BR-F6-4).
                BimbinganSubmission::query()
                    ->where('pengajuan_siswa_id', $pengajuan->getKey())
                    ->where('is_archived', false)
                    ->update(['is_archived' => true, 'archived_at' => now()]);
            }

            return $affected;
        });
    }
}
