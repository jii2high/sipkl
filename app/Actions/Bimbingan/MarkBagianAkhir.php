<?php

namespace App\Actions\Bimbingan;

use App\Actions\Action;
use App\Models\BimbinganSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Siswa menandai satu unggahan sebagai "Bagian Akhir" (F6, BR-F6-2). Guard server:
 * minimal `pkl.bimbingan.min_interactions` interaksi bimbingan yang tidak ter-archive.
 */
final class MarkBagianAkhir extends Action
{
    public function handle(mixed ...$arguments): BimbinganSubmission
    {
        [$siswa, $submission] = $arguments;

        if (! $siswa instanceof User || ! $submission instanceof BimbinganSubmission) {
            throw new RuntimeException('Siswa dan bimbingan wajib diisi.');
        }

        $pengajuan = $submission->pengajuanSiswa()->firstOrFail();

        Gate::forUser($siswa)->authorize('create', [BimbinganSubmission::class, $pengajuan]);

        if ($submission->is_archived) {
            throw ValidationException::withMessages(['bagian_akhir' => __('pkl.bimbingan.archived')]);
        }

        $this->assertEnoughInteractions((int) $pengajuan->getKey());

        return $this->transaction(function () use ($submission, $siswa): BimbinganSubmission {
            $submission->update(['is_bagian_akhir' => true]);

            $this->audit(
                $submission,
                'bimbingan_bagian_akhir',
                null,
                'bagian_akhir',
                ['oleh' => $siswa->getKey()],
                'Bagian akhir laporan ditandai',
            );

            return $submission;
        });
    }

    private function assertEnoughInteractions(int $pengajuanSiswaId): void
    {
        $min = (int) config('pkl.bimbingan.min_interactions');

        $count = BimbinganSubmission::query()
            ->where('pengajuan_siswa_id', $pengajuanSiswaId)
            ->where('is_archived', false)
            ->count();

        if ($count < $min) {
            throw ValidationException::withMessages([
                'bagian_akhir' => __('pkl.bimbingan.min_interactions', ['min' => $min, 'count' => $count]),
            ]);
        }
    }
}
