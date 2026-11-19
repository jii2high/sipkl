<?php

namespace App\Actions\Pengajuan;

use App\Enums\PengajuanStatus;
use App\Models\PengajuanSiswa;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Hubin mencatat dokumen fisik sudah diterima (F3 alur 7). Hanya mengubah
 * `is_provisional` dan jejak konfirmasi; keputusan Kaprog tidak berubah.
 */
final class ConfirmPhysicalDocument extends PengajuanAction
{
    public function handle(mixed ...$arguments): int
    {
        /** @var PengajuanSiswa $pengajuan */
        /** @var User $hubin */
        [$pengajuan, $hubin] = $arguments;

        if ($pengajuan->status !== PengajuanStatus::SedangPkl->value) {
            throw ValidationException::withMessages([
                'pengajuan' => __('pkl.pengajuan.physical_only_sedang_pkl'),
            ]);
        }

        $this->authorize('confirmPhysicalDocument', $pengajuan, $hubin);

        if (! $pengajuan->is_provisional && $pengajuan->dokumen_fisik_diterima_at !== null) {
            return 0;
        }

        return $this->transaction(function () use ($pengajuan, $hubin): int {
            $affected = $pengajuan->newQuery()
                ->whereKey($pengajuan->getKey())
                ->where('is_provisional', true)
                ->update([
                    'is_provisional' => false,
                    'dokumen_fisik_diterima_at' => now(),
                    'dokumen_fisik_confirmed_by' => $hubin->getKey(),
                ]);

            if ($affected === 1) {
                $this->audit(
                    $pengajuan,
                    'dokumen_fisik_diterima',
                    PengajuanStatus::SedangPkl->value,
                    PengajuanStatus::SedangPkl->value,
                    ['oleh' => $hubin->getKey()],
                );
            }

            return $affected;
        });
    }
}
