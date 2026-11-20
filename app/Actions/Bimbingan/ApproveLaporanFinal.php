<?php

namespace App\Actions\Bimbingan;

use App\Actions\Action;
use App\Enums\BimbinganStatus;
use App\Enums\PengajuanStatus;
use App\Models\BimbinganSubmission;
use App\Models\DocumentSignature;
use App\Models\LaporanVersion;
use App\Models\PengajuanSiswa;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Guru menyetujui laporan final (F6, KP-F6-4): satu aksi membuat `laporan_versions` v1
 * dan membuka rantai 5-TTD dengan step 1 `signed` + step 2 `pending`.
 */
final class ApproveLaporanFinal extends Action
{
    public function handle(mixed ...$arguments): LaporanVersion
    {
        [$guru, $pengajuan, $submission] = $arguments;

        if (! $guru instanceof User || ! $pengajuan instanceof PengajuanSiswa || ! $submission instanceof BimbinganSubmission) {
            throw new RuntimeException('Guru, pengajuan, dan bimbingan bagian akhir wajib diisi.');
        }

        Gate::forUser($guru)->authorize('approveFinal', $submission);

        $this->assertEligible($pengajuan, $submission);

        return $this->transaction(function () use ($guru, $pengajuan, $submission): LaporanVersion {
            // Idempoten: ACC kedua tidak membuat versi baru.
            $existing = LaporanVersion::query()
                ->where('pengajuan_siswa_id', $pengajuan->getKey())
                ->orderBy('version_number')
                ->first();

            if ($existing !== null) {
                return $existing;
            }

            $version = LaporanVersion::query()->create([
                'pengajuan_siswa_id' => $pengajuan->getKey(),
                'version_number' => 1,
                'source_bimbingan_submission_id' => $submission->getKey(),
                'file_path' => $submission->file_path,
                'created_by' => $guru->getKey(),
            ]);

            DocumentSignature::query()->create([
                'pengajuan_siswa_id' => $pengajuan->getKey(),
                'laporan_version_id' => $version->getKey(),
                'step_order' => 1,
                'signer_type' => 'guru_pembimbing',
                'signer_user_id' => $guru->getKey(),
                'status' => 'signed',
                'signed_at' => now(),
            ]);

            DocumentSignature::query()->create([
                'pengajuan_siswa_id' => $pengajuan->getKey(),
                'laporan_version_id' => $version->getKey(),
                'step_order' => 2,
                'signer_type' => 'kaprog',
                'status' => 'pending',
            ]);

            $this->audit(
                $version,
                'laporan_final_disetujui',
                null,
                'signed',
                ['oleh' => $guru->getKey(), 'step' => 1],
                'Laporan final disetujui guru; rantai pengesahan dibuka',
            );

            return $version;
        });
    }

    private function assertEligible(PengajuanSiswa $pengajuan, BimbinganSubmission $submission): void
    {
        if ($pengajuan->status !== PengajuanStatus::SedangPkl->value || $pengajuan->is_provisional) {
            throw ValidationException::withMessages(['laporan' => __('pkl.bimbingan.provisional_locked')]);
        }

        if ((int) $submission->pengajuan_siswa_id !== (int) $pengajuan->getKey()) {
            throw ValidationException::withMessages(['laporan' => __('pkl.bimbingan.mismatch')]);
        }

        if ($submission->is_archived) {
            throw ValidationException::withMessages(['laporan' => __('pkl.bimbingan.archived')]);
        }

        if (! $submission->is_bagian_akhir) {
            throw ValidationException::withMessages(['laporan' => __('pkl.bimbingan.not_final')]);
        }

        if ($submission->status !== BimbinganStatus::Diterima->value) {
            throw ValidationException::withMessages(['laporan' => __('pkl.bimbingan.not_accepted')]);
        }
    }
}
