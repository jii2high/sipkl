<?php

namespace App\Actions\Pengajuan;

use App\Enums\PengajuanStatus;
use App\Models\PengajuanSiswa;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Siswa mengunggah surat balasan perusahaan (F3 alur 5). Berkas sudah divalidasi
 * dan disimpan `FileStorageService` oleh pemanggil; Action mencatat path + hasil
 * lalu memindahkan status ke `menunggu_balasan_perusahaan`.
 */
final class SubmitSuratBalasan extends PengajuanAction
{
    public function handle(mixed ...$arguments): int
    {
        /** @var PengajuanSiswa $pengajuan */
        /** @var User $siswa */
        /** @var string $path */
        [$pengajuan, $siswa, $path] = $arguments;

        $hasil = $arguments[3] ?? null;

        if ($pengajuan->status === PengajuanStatus::MenungguBalasanPerusahaan->value) {
            return 0;
        }

        $this->assertTransitionable($pengajuan, PengajuanStatus::MenungguBalasanPerusahaan, 'siswa');
        $this->authorize('uploadSuratBalasan', $pengajuan, $siswa);

        if (! is_string($path) || trim($path) === '') {
            throw ValidationException::withMessages([
                'surat_balasan' => __('pkl.pengajuan.surat_balasan_required'),
            ]);
        }

        if ($hasil !== null && ! in_array($hasil, ['diterima', 'ditolak'], true)) {
            throw ValidationException::withMessages([
                'surat_balasan_hasil' => __('pkl.pengajuan.surat_balasan_hasil_invalid'),
            ]);
        }

        return $this->transaction(function () use ($pengajuan, $path, $hasil): int {
            $affected = $this->transition(
                $pengajuan,
                PengajuanStatus::MenungguBalasanPerusahaan,
                'siswa',
                [
                    'surat_balasan_path' => $path,
                    'surat_balasan_hasil' => $hasil,
                    'surat_balasan_uploaded_at' => now(),
                ],
            );

            if ($affected === 1) {
                $this->audit(
                    $pengajuan,
                    'surat_balasan_diunggah',
                    PengajuanStatus::SuratPengantarTerbit->value,
                    PengajuanStatus::MenungguBalasanPerusahaan->value,
                    ['hasil' => $hasil],
                );
            }

            return $affected;
        });
    }
}
