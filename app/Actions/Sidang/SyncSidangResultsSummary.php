<?php

namespace App\Actions\Sidang;

use App\Actions\Action;
use App\Models\Company;
use App\Models\FormDefinition;
use App\Models\FormSubmission;
use App\Models\SidangResultsSummary;
use RuntimeException;

/**
 * Isi ulang `sidang_results_summary` setiap nilai sidang dikunci atau direvisi
 * (TRD.md Lampiran 15). Hanya field PD-08; `status_kelulusan` dan catatannya
 * dipertahankan karena diisi manual Kaprog (Q-I).
 */
final class SyncSidangResultsSummary extends Action
{
    public function handle(mixed ...$arguments): ?SidangResultsSummary
    {
        [$submission] = $arguments;

        if (! $submission instanceof FormSubmission) {
            throw new RuntimeException('Submission wajib diisi.');
        }

        $submission->loadMissing(['definition.sections.items', 'jadwalSidang.externalExaminer', 'pengajuanSiswa.siswa.jurusan']);

        if ($submission->form_type !== FormDefinition::TYPE_SIDANG || ! $submission->is_submitted) {
            return null;
        }

        $pengajuan = $submission->pengajuanSiswa;
        $jadwal = $submission->jadwalSidang;

        if ($pengajuan === null || $jadwal === null) {
            return null;
        }

        $snapshot = (array) ($pengajuan->snapshot_company_json ?? []);
        $companyName = (string) ($snapshot['nama'] ?? Company::query()->whereKey($pengajuan->company_id)->value('nama') ?? '-');

        return $this->transaction(fn (): SidangResultsSummary => SidangResultsSummary::query()->updateOrCreate(
            ['form_submission_id' => $submission->getKey()],
            [
                'pengajuan_siswa_id' => $pengajuan->getKey(),
                'nama_siswa' => (string) ($pengajuan->siswa?->name ?? '-'),
                'nis' => (string) ($pengajuan->siswa?->nis_nip ?? '-'),
                'jurusan' => (string) ($pengajuan->siswa?->jurusan?->nama ?? '-'),
                'nama_perusahaan' => $companyName,
                'academic_period_id' => $pengajuan->academic_period_id,
                'nilai_total_per_kategori' => $this->perKategori($submission),
                'nama_penguji' => (string) ($jadwal->externalExaminer?->nama ?? '-'),
                'tanggal_sidang' => $jadwal->tanggal->toDateString(),
                'synced_at' => now(),
            ],
        ));
    }

    /** Rata-rata nilai per section; section tanpa item tidak menghasilkan entri. */
    private function perKategori(FormSubmission $submission): array
    {
        $jawaban = (array) ($submission->jawaban['items'] ?? []);
        $result = [];

        foreach ($submission->definition?->sections ?? [] as $section) {
            $nilai = [];

            foreach ($section->items as $item) {
                $value = $jawaban[$item->getKey()]['nilai'] ?? null;

                if (is_numeric($value)) {
                    $nilai[] = (float) $value;
                }
            }

            if ($nilai !== []) {
                $result[$section->label] = round(array_sum($nilai) / count($nilai), 2);
            }
        }

        return $result;
    }
}
