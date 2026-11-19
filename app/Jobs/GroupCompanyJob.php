<?php

namespace App\Jobs;

use App\Enums\PengajuanPklStatus;
use App\Models\Company;
use App\Models\PengajuanPkl;
use App\Models\PengajuanSiswa;
use App\Services\AuditService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

/**
 * Masukkan siswa ke company group `(company, periode)` setelah TTD Walas
 * (TRD.md §10, F3 alur 4). Idempoten: baris pengajuan dikunci lebih dulu, lalu
 * baris perusahaan dikunci agar dua job bersamaan tidak membuat dua group.
 */
class GroupCompanyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(public int $pengajuanSiswaId)
    {
        $this->afterCommit = true;
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(): void
    {
        DB::transaction(function (): void {
            $pengajuan = PengajuanSiswa::query()
                ->whereKey($this->pengajuanSiswaId)
                ->lockForUpdate()
                ->first();

            if ($pengajuan === null || $pengajuan->pengajuan_pkl_id !== null) {
                return;
            }

            if (! in_array($pengajuan->status, [
                'menunggu_surat_pengantar',
                'surat_pengantar_terbit',
                'menunggu_balasan_perusahaan',
                'sedang_pkl',
            ], true)) {
                return;
            }

            // Serialisasi pembentukan group per perusahaan. Tabel pengajuan_pkl tidak
            // punya UNIQUE (company, periode), jadi lock ini yang mencegah group ganda.
            Company::query()->whereKey($pengajuan->company_id)->lockForUpdate()->first();

            $group = PengajuanPkl::query()->firstOrCreate(
                [
                    'company_id' => $pengajuan->company_id,
                    'academic_period_id' => $pengajuan->academic_period_id,
                ],
                ['status' => PengajuanPklStatus::Terbentuk->value],
            );

            $pengajuan->newQuery()
                ->whereKey($pengajuan->getKey())
                ->whereNull('pengajuan_pkl_id')
                ->update(['pengajuan_pkl_id' => $group->getKey()]);

            app(AuditService::class)->log(
                description: 'Siswa dimasukkan ke company group',
                subject: $pengajuan,
                event: 'grouping',
                properties: [
                    'pengajuan_pkl_id' => $group->getKey(),
                    'company_id' => $pengajuan->company_id,
                    'academic_period_id' => $pengajuan->academic_period_id,
                ],
            );
        });
    }
}
