<?php

namespace App\Actions\Pengesahan;

use App\Actions\Action;
use App\Enums\PengajuanStatus;
use App\Models\DocumentSignature;
use App\Models\LaporanVersion;
use App\Models\PengajuanSiswa;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Siswa mengunggah revisi setelah satu step ditolak (F7). Versi baru dibuat dan
 * rantai kembali langsung ke step yang menolak; step sebelumnya tetap signed.
 */
final class UploadRevisiLaporan extends Action
{
    public function __construct(private readonly FileStorageService $files) {}

    public function handle(mixed ...$arguments): LaporanVersion
    {
        [$siswa, $pengajuan, $file] = $arguments;
        $note = $arguments[3] ?? null;

        if (! $siswa instanceof User || ! $pengajuan instanceof PengajuanSiswa || ! $file instanceof UploadedFile) {
            throw new RuntimeException('Siswa, pengajuan, dan berkas revisi wajib diisi.');
        }

        if ((int) $pengajuan->siswa_id !== (int) $siswa->getKey()
            || $pengajuan->status !== PengajuanStatus::SedangPkl->value) {
            throw new AuthorizationException(__('pkl.pengesahan.not_owner'));
        }

        $path = $this->files->store($file, 'bimbingan');

        return $this->transaction(function () use ($siswa, $pengajuan, $path, $note): LaporanVersion {
            $latest = LaporanVersion::query()
                ->where('pengajuan_siswa_id', $pengajuan->getKey())
                ->orderByDesc('version_number')
                ->lockForUpdate()
                ->first();

            if ($latest === null) {
                throw ValidationException::withMessages(['berkas' => __('pkl.pengesahan.no_report')]);
            }

            $rejected = DocumentSignature::query()
                ->where('laporan_version_id', $latest->getKey())
                ->where('status', 'rejected')
                ->orderByDesc('step_order')
                ->first();

            if ($rejected === null) {
                throw ValidationException::withMessages(['berkas' => __('pkl.pengesahan.not_rejected_step')]);
            }

            $version = LaporanVersion::query()->create([
                'pengajuan_siswa_id' => $pengajuan->getKey(),
                'version_number' => (int) $latest->version_number + 1,
                'source_bimbingan_submission_id' => $latest->source_bimbingan_submission_id,
                'file_path' => $path,
                'created_by' => $siswa->getKey(),
            ]);

            DocumentSignature::query()->create([
                'pengajuan_siswa_id' => $pengajuan->getKey(),
                'laporan_version_id' => $version->getKey(),
                'step_order' => $rejected->step_order,
                'signer_type' => $rejected->signer_type,
                'signer_external_name' => $rejected->signer_external_name,
                'signer_external_contact' => $rejected->signer_external_contact,
                'status' => 'pending',
            ]);

            $this->audit(
                $version,
                'laporan_revisi_diupload',
                'rejected',
                'pending',
                [
                    'versi' => $version->version_number,
                    'step' => $rejected->step_order,
                    'catatan' => $note,
                    'oleh' => $siswa->getKey(),
                ],
                'Revisi laporan diunggah; rantai dilanjutkan dari step '.$rejected->step_order,
            );

            return $version;
        });
    }
}
