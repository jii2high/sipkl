<?php

namespace App\Actions\Pengajuan;

use App\Enums\PengajuanStatus;
use App\Exceptions\QuotaExceeded;
use App\Models\Company;
use App\Models\PengajuanSiswa;
use App\Models\User;
use App\Services\QuotaService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Kaprog menerima siswa di perusahaan (F3 alur 6). Kuota dihitung ulang di dalam
 * transaksi setelah baris `companies` dikunci, sehingga dua penerimaan bersamaan
 * tidak dapat sama-sama lolos pada slot terakhir (TRD.md §8).
 *
 * `is_provisional` diisi dari ada/tidaknya dokumen fisik saat penerimaan; Hubin
 * dapat mengubahnya nanti lewat `ConfirmPhysicalDocument` tanpa mengubah
 * keputusan Kaprog.
 */
final class AcceptByCompany extends PengajuanAction
{
    public function __construct(private readonly QuotaService $quota) {}

    /**
     * @param  array{tanggal_mulai?: string|null, tanggal_selesai?: string|null, has_physical_document?: bool}  $data
     */
    public function handle(mixed ...$arguments): int
    {
        /** @var PengajuanSiswa $pengajuan */
        /** @var User $kaprog */
        [$pengajuan, $kaprog] = $arguments;

        /** @var array<string, mixed> $data */
        $data = $arguments[2] ?? [];

        if ($pengajuan->status === PengajuanStatus::SedangPkl->value) {
            return 0;
        }

        $this->assertTransitionable($pengajuan, PengajuanStatus::SedangPkl, 'kaprog');
        $this->authorize('acceptByCompany', $pengajuan, $kaprog);

        return $this->transaction(function () use ($pengajuan, $kaprog, $data): int {
            $company = Company::query()->whereKey($pengajuan->company_id)->lockForUpdate()->firstOrFail();

            $used = $this->quota->used((int) $pengajuan->company_id, (int) $pengajuan->academic_period_id);
            $maks = $this->quota->maximum();

            if ($used >= $maks) {
                throw QuotaExceeded::forCompany((string) $company->nama, $maks);
            }

            $period = $pengajuan->academicPeriod;

            if ($period === null) {
                throw ValidationException::withMessages([
                    'pengajuan' => __('pkl.pengajuan.period_missing'),
                ]);
            }

            [$mulai, $selesai] = $this->dates($period->tanggal_mulai, $period->tanggal_selesai, $data);

            $provisional = ! (bool) ($data['has_physical_document'] ?? false);

            $affected = $this->transition(
                $pengajuan,
                PengajuanStatus::SedangPkl,
                'kaprog',
                [
                    'accepted_by' => $kaprog->getKey(),
                    'accepted_at' => now(),
                    'is_provisional' => $provisional,
                    'tanggal_mulai_pkl' => $mulai,
                    'tanggal_selesai_pkl' => $selesai,
                ],
            );

            if ($affected === 1) {
                $this->audit(
                    $pengajuan,
                    'diterima_perusahaan',
                    PengajuanStatus::MenungguBalasanPerusahaan->value,
                    PengajuanStatus::SedangPkl->value,
                    [
                        'company_id' => $pengajuan->company_id,
                        'kuota_terpakai' => $used + 1,
                        'kuota_maks' => $maks,
                        'is_provisional' => $provisional,
                        'oleh' => $kaprog->getKey(),
                    ],
                );
            }

            return $affected;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: string}
     */
    private function dates(mixed $defaultMulai, mixed $defaultSelesai, array $data): array
    {
        $mulai = $data['tanggal_mulai'] ?? $defaultMulai;
        $selesai = $data['tanggal_selesai'] ?? $defaultSelesai;

        if ($mulai === null || $selesai === null) {
            throw ValidationException::withMessages([
                'tanggal_mulai' => __('pkl.pengajuan.tanggal_required'),
            ]);
        }

        $mulai = Carbon::parse($mulai)->toDateString();
        $selesai = Carbon::parse($selesai)->toDateString();

        if ($selesai < $mulai) {
            throw ValidationException::withMessages([
                'tanggal_selesai' => __('pkl.pengajuan.tanggal_order'),
            ]);
        }

        return [$mulai, $selesai];
    }
}
