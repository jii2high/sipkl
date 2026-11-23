<?php

namespace App\Services;

use App\Actions\Pengesahan\PengesahanAction;
use App\Enums\PengajuanStatus;
use App\Enums\SignatureStatus;
use App\Models\DocumentSignature;
use App\Models\LaporanVersion;
use App\Models\PengajuanSiswa;
use App\Support\JakartaTime;
use Illuminate\Support\Collection;

/**
 * Angka dashboard monitoring (F15): lama tunggu per gate pengajuan dan per step
 * 5-TTD. Semua durasi memakai hari kerja Senin-Jumat; libur nasional tidak
 * dikecualikan (non-goal PRD).
 */
final class MonitoringService
{
    public function __construct(private readonly QuotaService $quota) {}

    /**
     * Hanya step pending pada versi laporan terbaru yang belum legit.
     *
     * @return array<int, array{step: int, label: string, pending: int, rata: int, terlama: int}>
     */
    public function ttdSteps(): array
    {
        $pending = DocumentSignature::query()
            ->where('status', SignatureStatus::Pending->value)
            ->whereHas('laporanVersion', fn ($query) => $query
                ->where('is_legit', false)
                ->whereIn('id', LaporanVersion::query()->selectRaw('MAX(id)')->groupBy('pengajuan_siswa_id')))
            ->get(['step_order', 'created_at']);

        $steps = [];

        foreach (PengesahanAction::STEP_LABELS as $step => $label) {
            $rows = $pending->where('step_order', $step);

            $steps[] = [
                'step' => $step,
                'label' => $label,
                'pending' => $rows->count(),
                'rata' => $this->averageDays($rows),
                'terlama' => $this->oldestDays($rows),
            ];
        }

        return $steps;
    }

    /**
     * Antrean gate pengajuan beserta peringatan kuota perusahaan pada antrean itu.
     *
     * @return array<int, array{status: string, jumlah: int, rata: int, terlama: int, kuota: array<int, array{nama: string, used: int, maks: int, tone: string}>}>
     */
    public function gates(): array
    {
        $statuses = [
            PengajuanStatus::MenungguVerifikasiKaprog->value,
            PengajuanStatus::MenungguTtdWalas->value,
            PengajuanStatus::MenungguSuratPengantar->value,
            PengajuanStatus::MenungguBalasanPerusahaan->value,
        ];

        $rows = PengajuanSiswa::query()
            ->whereIn('status', $statuses)
            ->with('company:id,nama')
            ->get(['id', 'status', 'company_id', 'academic_period_id', 'updated_at']);

        $used = $this->quotaByCompany($rows);

        return array_map(function (string $status) use ($rows, $used): array {
            $queue = $rows->where('status', $status);

            return [
                'status' => $status,
                'jumlah' => $queue->count(),
                'rata' => $this->averageDays($queue, 'updated_at'),
                'terlama' => $this->oldestDays($queue, 'updated_at'),
                'kuota' => $queue
                    ->map(function (PengajuanSiswa $row) use ($used): ?array {
                        $key = $row->company_id.':'.$row->academic_period_id;
                        $dipakai = $used[$key] ?? 0;
                        $tone = $this->quota->tone($dipakai);

                        if ($tone === 'neutral') {
                            return null;
                        }

                        return [
                            'kunci' => $key,
                            'nama' => $row->company?->nama ?? '-',
                            'used' => $dipakai,
                            'maks' => $this->quota->maximum(),
                            'tone' => $tone,
                        ];
                    })
                    ->filter()
                    ->unique('kunci')
                    ->map(fn (array $warning): array => array_diff_key($warning, ['kunci' => true]))
                    ->values()
                    ->all(),
            ];
        }, $statuses);
    }

    /**
     * @param  Collection<int, PengajuanSiswa>  $rows
     * @return array<string, int>
     */
    private function quotaByCompany(Collection $rows): array
    {
        $companyIds = $rows->pluck('company_id')->unique()->values();

        if ($companyIds->isEmpty()) {
            return [];
        }

        $counts = PengajuanSiswa::query()
            ->where('status', PengajuanStatus::SedangPkl->value)
            ->whereIn('company_id', $companyIds)
            ->selectRaw('company_id, academic_period_id, COUNT(*) AS used')
            ->groupBy('company_id', 'academic_period_id')
            ->get();

        return $counts
            ->mapWithKeys(fn ($row): array => [$row->company_id.':'.$row->academic_period_id => (int) $row->used])
            ->all();
    }

    private function averageDays(Collection $rows, string $column = 'created_at'): int
    {
        if ($rows->isEmpty()) {
            return 0;
        }

        return (int) round($rows->avg(fn ($row): int => JakartaTime::workingDaysElapsed($row->{$column})));
    }

    private function oldestDays(Collection $rows, string $column = 'created_at'): int
    {
        return (int) $rows
            ->map(fn ($row): int => JakartaTime::workingDaysElapsed($row->{$column}))
            ->max();
    }
}
