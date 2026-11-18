<?php

namespace App\Imports;

use App\Models\AcademicPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Impor massal periode akademik (TASKS M2.9).
 *
 * Kolom tahun ajaran menerima "2026/2027" maupun "2026-2027"; tanggal menerima
 * sel tanggal Excel, "2026-07-01", atau "01/07/2026" (hari/bulan/tahun).
 */
final class PeriodsImport extends BaseImport
{
    /**
     * @param  array<string, mixed>  $row
     */
    protected function prosesBaris(array $row, int $nomor): void
    {
        $tahunAjaran = $this->tahunAjaran($this->ambil($row, 'tahun_ajaran'));
        $batch = $this->batch($this->ambil($row, 'batch'));
        $mulai = $this->tanggal($this->ambil($row, 'tanggal_mulai'));
        $selesai = $this->tanggal($this->ambil($row, 'tanggal_selesai'));

        Validator::make(
            [
                'tahun_ajaran' => $tahunAjaran,
                'batch' => $batch,
                'tanggal_mulai' => $mulai,
                'tanggal_selesai' => $selesai,
                'is_active' => $this->boolean($this->ambil($row, 'aktif', 'is_active')),
                'locked_for_new_submission' => $this->boolean($this->ambil($row, 'kunci_pengajuan_baru', 'locked_for_new_submission'), false),
            ],
            [
                'tahun_ajaran' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
                'batch' => ['required', Rule::in(['1', '2'])],
                'tanggal_mulai' => ['required', 'date_format:Y-m-d'],
                'tanggal_selesai' => ['required', 'date_format:Y-m-d', 'after_or_equal:tanggal_mulai'],
                'is_active' => ['boolean'],
                'locked_for_new_submission' => ['boolean'],
            ],
            [
                'tahun_ajaran.required' => 'Tahun ajaran wajib diisi.',
                'tahun_ajaran.regex' => 'Tahun ajaran harus berformat YYYY/YYYY, misalnya 2026/2027.',
                'batch.required' => 'Batch wajib diisi.',
                'batch.in' => 'Batch harus 1 atau 2.',
                'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
                'tanggal_mulai.date_format' => 'Tanggal mulai tidak dikenali; pakai format YYYY-MM-DD.',
                'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
                'tanggal_selesai.date_format' => 'Tanggal selesai tidak dikenali; pakai format YYYY-MM-DD.',
                'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            ],
        )->validate();

        $sudahAda = AcademicPeriod::query()
            ->where('tahun_ajaran', $tahunAjaran)
            ->where('batch', $batch)
            ->exists();

        if ($sudahAda) {
            throw ValidationException::withMessages([
                'tahun_ajaran' => 'Periode '.$tahunAjaran.' batch '.$batch.' sudah terdaftar.',
            ]);
        }

        AcademicPeriod::query()->create([
            'tahun_ajaran' => $tahunAjaran,
            'batch' => $batch,
            'tanggal_mulai' => $mulai,
            'tanggal_selesai' => $selesai,
            'is_active' => $this->boolean($this->ambil($row, 'aktif', 'is_active')),
            'locked_for_new_submission' => $this->boolean($this->ambil($row, 'kunci_pengajuan_baru', 'locked_for_new_submission'), false),
        ]);

        $this->report->sukses($nomor, $tahunAjaran.' batch '.$batch);
    }

    private function tahunAjaran(?string $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        $nilai = preg_replace('/\s+/', '', $nilai) ?? $nilai;
        $nilai = str_replace(['-', '–', '—'], '/', $nilai);

        return trim(preg_replace('#/+#', '/', $nilai) ?? $nilai, '/');
    }

    private function batch(?string $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        if (preg_match('/(\d+)/', $nilai, $cocok) === 1) {
            return $cocok[1];
        }

        return null;
    }

    private function tanggal(?string $nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }

        if (is_numeric($nilai) && (float) $nilai > 0) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $nilai)->format('Y-m-d');
            } catch (Throwable) {
                return null;
            }
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd M Y'] as $format) {
            if (! CarbonImmutable::hasFormat($nilai, $format)) {
                continue;
            }

            $tanggal = CarbonImmutable::createFromFormat($format, $nilai);

            if ($tanggal !== false) {
                return $tanggal->format('Y-m-d');
            }
        }

        return null;
    }
}
