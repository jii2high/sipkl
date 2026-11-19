<?php

namespace App\Services;

use App\Enums\PengajuanStatus;
use App\Models\PengajuanSiswa;

/**
 * Hitung kuota PKL per company+periode (BR-F3-3). Yang dihitung hanya status
 * `sedang_pkl`; `is_provisional` ikut karena masih memakai slot, sedangkan
 * `rejected` dan `withdrawn` otomatis melepas slot. Batas dibaca dari
 * `config('pkl.quota_per_company_period')`.
 *
 * Pemanggil yang memutuskan penerimaan **wajib** mengunci baris `companies`
 * lebih dulu lalu memanggil `used()` di dalam transaksi yang sama (TRD.md §8).
 */
final class QuotaService
{
    public function maximum(): int
    {
        return (int) config('pkl.quota_per_company_period');
    }

    public function used(int $companyId, int $academicPeriodId): int
    {
        return PengajuanSiswa::query()
            ->where('company_id', $companyId)
            ->where('academic_period_id', $academicPeriodId)
            ->where('status', PengajuanStatus::SedangPkl->value)
            ->count();
    }

    public function remaining(int $companyId, int $academicPeriodId): int
    {
        return max(0, $this->maximum() - $this->used($companyId, $academicPeriodId));
    }

    /** Tone Design.md: ≤ 4 netral, 5 peringatan, 6 bahaya. */
    public function tone(int $used): string
    {
        $maks = $this->maximum();

        return match (true) {
            $used >= $maks => 'danger',
            $used >= $maks - 1 => 'warning',
            default => 'neutral',
        };
    }

    public function label(int $used): string
    {
        return __('pkl.quota.label', ['used' => $used, 'maks' => $this->maximum()]);
    }
}
