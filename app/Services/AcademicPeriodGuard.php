<?php

namespace App\Services;

use App\Models\AcademicPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Penjaga penghapusan periode akademik (TASKS M2.6).
 *
 * Periode yang sudah punya data terkait tidak boleh dihapus supaya histori
 * pengajuan/plotting/nilai tidak menggantung. Tabel yang belum ada pada
 * milestone berjalan dilewati otomatis, jadi daftar ini aman dipakai ulang
 * saat tabel terkait muncul di milestone berikutnya.
 */
final class AcademicPeriodGuard
{
    /**
     * @var array<string, string> tabel => kolom yang menunjuk academic_periods.id
     */
    public const TABEL_TERKAIT = [
        'pengajuan_pkl' => 'academic_period_id',
        'pengajuan_siswa' => 'academic_period_id',
        'guru_pembimbing_plotting' => 'academic_period_id',
        'form_definitions' => 'academic_period_id',
        'form_submissions' => 'academic_period_id',
        'sidang_results_summary' => 'academic_period_id',
        'accreditation_exports' => 'academic_period_id',
    ];

    /**
     * @param  array<string, string>  $tabelTerkait  Override daftar tabel (dipakai test).
     */
    public function __construct(private readonly array $tabelTerkait = self::TABEL_TERKAIT) {}

    public function punyaDataTerkait(AcademicPeriod $period): bool
    {
        foreach ($this->tabelTerkait as $tabel => $kolom) {
            if (! Schema::hasTable($tabel) || ! Schema::hasColumn($tabel, $kolom)) {
                continue;
            }

            if (DB::table($tabel)->where($kolom, $period->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws ValidationException
     */
    public function pastikanBolehDihapus(AcademicPeriod $period): void
    {
        if ($this->punyaDataTerkait($period)) {
            throw ValidationException::withMessages([
                'period' => 'Periode ini sudah punya data terkait sehingga tidak bisa dihapus.',
            ]);
        }
    }
}
