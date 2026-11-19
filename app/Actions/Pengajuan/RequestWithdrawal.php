<?php

namespace App\Actions\Pengajuan;

use App\Enums\PengajuanStatus;
use App\Models\PengajuanSiswa;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Siswa atau Kaprog mengajukan penarikan PKL (F3 alur 9). Status tidak berubah
 * sampai Kaprog menyetujui; alasan wajib dan tersimpan di audit.
 */
final class RequestWithdrawal extends PengajuanAction
{
    public function handle(mixed ...$arguments): int
    {
        /** @var PengajuanSiswa $pengajuan */
        /** @var User $actor */
        [$pengajuan, $actor, $reason] = $arguments + [null, null, null];

        if ($pengajuan->status !== PengajuanStatus::SedangPkl->value) {
            throw ValidationException::withMessages([
                'withdraw_reason' => __('pkl.pengajuan.withdraw_only_sedang_pkl'),
            ]);
        }

        $this->authorize('requestWithdrawal', $pengajuan, $actor);

        if ($pengajuan->withdraw_requested_at !== null) {
            return 0;
        }

        $reason = trim((string) $reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'withdraw_reason' => __('pkl.pengajuan.withdraw_reason_required'),
            ]);
        }

        return $this->transaction(function () use ($pengajuan, $actor, $reason): int {
            $affected = $pengajuan->newQuery()
                ->whereKey($pengajuan->getKey())
                ->whereNull('withdraw_requested_at')
                ->update([
                    'withdraw_requested_at' => now(),
                    'withdraw_requested_by' => $actor->getKey(),
                    'withdraw_reason' => $reason,
                ]);

            if ($affected === 1) {
                $this->audit(
                    $pengajuan,
                    'penarikan_diajukan',
                    PengajuanStatus::SedangPkl->value,
                    PengajuanStatus::SedangPkl->value,
                    ['alasan' => $reason, 'oleh' => $actor->getKey()],
                );
            }

            return $affected;
        });
    }
}
