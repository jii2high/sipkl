<?php

namespace App\Actions\Sidang;

use App\Actions\Action;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\JadwalSidang;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Buka submission sidang untuk satu jadwal (F9): form yang dipakai adalah
 * definisi `sidang` terbit terakhir pada jurusan + periode siswa. Idempoten —
 * submission yang sudah ada dikembalikan apa adanya (uq_submission_jadwal).
 */
final class OpenSidangSubmission extends Action
{
    public function handle(mixed ...$arguments): FormSubmission
    {
        [$jadwal] = $arguments;

        if (! $jadwal instanceof JadwalSidang) {
            throw new RuntimeException('Jadwal sidang wajib diisi.');
        }

        return $this->transaction(function () use ($jadwal): FormSubmission {
            $existing = FormSubmission::query()->where('jadwal_sidang_id', $jadwal->getKey())->first();

            if ($existing !== null) {
                return $existing;
            }

            $pengajuan = $jadwal->pengajuanSiswa;
            $definition = FormDefinition::query()
                ->where('form_type', FormDefinition::TYPE_SIDANG)
                ->where('is_published', true)
                ->where('academic_period_id', $pengajuan?->academic_period_id)
                ->where('jurusan_id', $pengajuan?->siswa?->jurusan_id)
                ->orderByDesc('id')
                ->first();

            if ($definition === null) {
                throw ValidationException::withMessages(['form' => __('pkl.form.not_published')]);
            }

            return FormSubmission::query()->create([
                'form_definition_id' => $definition->getKey(),
                'form_type' => FormDefinition::TYPE_SIDANG,
                'jadwal_sidang_id' => $jadwal->getKey(),
                'pengajuan_siswa_id' => $pengajuan->getKey(),
                'jawaban' => ['items' => []],
            ]);
        });
    }
}
