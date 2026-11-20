<?php

namespace App\Actions\Bimbingan;

use App\Actions\Action;
use App\Enums\BimbinganStatus;
use App\Enums\PengajuanStatus;
use App\Models\BimbinganSubmission;
use App\Models\PengajuanSiswa;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Siswa mengunggah bimbingan (F6, BR-F6-1/3). Guard server: status `sedang_pkl`
 * dan bukan provisional, sehingga request langsung ke endpoint pun ditolak.
 */
final class SubmitBimbingan extends Action
{
    public function __construct(private readonly FileStorageService $files) {}

    public function handle(mixed ...$arguments): BimbinganSubmission
    {
        [$siswa, $pengajuan, $file] = $arguments;
        $keterangan = $arguments[3] ?? null;

        if (! $siswa instanceof User || ! $pengajuan instanceof PengajuanSiswa || ! $file instanceof UploadedFile) {
            throw new RuntimeException('Siswa, pengajuan, dan berkas bimbingan wajib diisi.');
        }

        Gate::forUser($siswa)->authorize('create', [BimbinganSubmission::class, $pengajuan]);

        $this->assertUnlocked($pengajuan);

        $path = $this->files->store($file, 'bimbingan');

        return $this->transaction(function () use ($pengajuan, $path, $keterangan, $siswa): BimbinganSubmission {
            $submission = BimbinganSubmission::query()->create([
                'pengajuan_siswa_id' => $pengajuan->getKey(),
                'file_path' => $path,
                'keterangan' => $keterangan,
                'status' => BimbinganStatus::MenungguFeedback->value,
            ]);

            $this->audit(
                $submission,
                'bimbingan_diupload',
                null,
                BimbinganStatus::MenungguFeedback->value,
                ['oleh' => $siswa->getKey()],
                'Bimbingan diunggah',
            );

            return $submission;
        });
    }

    private function assertUnlocked(PengajuanSiswa $pengajuan): void
    {
        if ($pengajuan->status !== PengajuanStatus::SedangPkl->value) {
            throw ValidationException::withMessages(['file' => __('pkl.bimbingan.only_sedang_pkl')]);
        }

        if ($pengajuan->is_provisional) {
            throw ValidationException::withMessages(['file' => __('pkl.bimbingan.provisional_locked')]);
        }
    }
}
