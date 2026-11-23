<?php

namespace App\Support;

use App\Enums\PengajuanStatus;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\PengajuanSiswa;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Window penilaian industri (F10): H-1 bulan sebelum `tanggal_selesai_pkl` siswa
 * aktif yang dicakup. Bila siswa satu perusahaan selesai pada tanggal berbeda,
 * acuannya tanggal paling awal agar tautan tidak terlambat dibuat.
 */
final class IndustryWindow
{
    public static function earliestEnd(Company $company, AcademicPeriod $period, int $jurusanId): ?CarbonImmutable
    {
        $date = PengajuanSiswa::query()
            ->where('company_id', $company->getKey())
            ->where('academic_period_id', $period->getKey())
            ->where('status', PengajuanStatus::SedangPkl->value)
            ->whereHas('siswa', fn (Builder $student) => $student->where('jurusan_id', $jurusanId))
            ->min('tanggal_selesai_pkl');

        return $date === null
            ? null
            : CarbonImmutable::parse((string) $date, JakartaTime::TZ)->startOfDay();
    }

    public static function opensAt(Company $company, AcademicPeriod $period, int $jurusanId): ?CarbonImmutable
    {
        return self::earliestEnd($company, $period, $jurusanId)?->subMonthNoOverflow();
    }

    /** Server tetap memeriksa window walau tombol UI hanya muncul saat terbuka (KP-F10-1). */
    public static function isOpen(Company $company, AcademicPeriod $period, int $jurusanId): bool
    {
        $opensAt = self::opensAt($company, $period, $jurusanId);

        return $opensAt !== null && JakartaTime::today()->greaterThanOrEqualTo($opensAt);
    }
}
