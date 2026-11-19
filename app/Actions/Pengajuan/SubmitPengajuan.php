<?php

namespace App\Actions\Pengajuan;

use App\Enums\PengajuanStatus;
use App\Models\PengajuanSiswa;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Kirim pengajuan (F3 alur 2). Snapshot perusahaan dibekukan di transisi ini dan
 * tidak pernah diubah lagi selama pengajuan berjalan (TRD.md §6.3, KP-F3-4).
 */
final class SubmitPengajuan extends PengajuanAction
{
    public function handle(mixed ...$arguments): int
    {
        /** @var PengajuanSiswa $pengajuan */
        /** @var User $siswa */
        [$pengajuan, $siswa] = $arguments;

        if ($pengajuan->status === PengajuanStatus::MenungguVerifikasiKaprog->value) {
            return 0;
        }

        $this->assertTransitionable($pengajuan, PengajuanStatus::MenungguVerifikasiKaprog, 'siswa');
        $this->authorize('submit', $pengajuan, $siswa);

        return $this->transaction(function () use ($pengajuan): int {
            $period = $pengajuan->academicPeriod;

            if ($period === null || $period->locked_for_new_submission) {
                throw ValidationException::withMessages([
                    'pengajuan' => __('pkl.pengajuan.period_locked'),
                ]);
            }

            $this->assertNoOtherActive($pengajuan);

            $company = $pengajuan->company;

            if ($company === null || $company->merged_into_id !== null) {
                throw ValidationException::withMessages([
                    'company_id' => __('pkl.pengajuan.company_missing'),
                ]);
            }

            $snapshot = [
                'id' => (int) $company->getKey(),
                'nama' => $company->nama,
                'alamat' => $company->alamat,
                'email' => $company->email,
                'website' => $company->website,
                'telp_fax' => $company->telp_fax,
                'cp_name' => $company->cp_name,
                'cp_phone' => $company->cp_phone,
                'pembimbing_industri_nama' => $company->pembimbing_industri_nama,
                'pembimbing_industri_email' => $company->pembimbing_industri_email,
                'kepala_industri_nama' => $company->kepala_industri_nama,
                'kepala_industri_email' => $company->kepala_industri_email,
                'source' => $company->source,
                'snapshot_at' => now()->toIso8601String(),
            ];

            $affected = $this->transition(
                $pengajuan,
                PengajuanStatus::MenungguVerifikasiKaprog,
                'siswa',
                ['snapshot_company_json' => json_encode($snapshot, JSON_UNESCAPED_UNICODE)],
            );

            if ($affected === 1) {
                $this->audit(
                    $pengajuan,
                    'pengajuan_diajukan',
                    PengajuanStatus::Draft->value,
                    PengajuanStatus::MenungguVerifikasiKaprog->value,
                );
            }

            return $affected;
        });
    }

    private function assertNoOtherActive(PengajuanSiswa $pengajuan): void
    {
        $conflict = PengajuanSiswa::query()
            ->where('siswa_id', $pengajuan->siswa_id)
            ->where('academic_period_id', $pengajuan->academic_period_id)
            ->whereKeyNot($pengajuan->getKey())
            ->whereNotIn('status', [
                PengajuanStatus::Rejected->value,
                PengajuanStatus::Withdrawn->value,
            ])
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'pengajuan' => __('pkl.pengajuan.active_exists'),
            ]);
        }
    }
}
