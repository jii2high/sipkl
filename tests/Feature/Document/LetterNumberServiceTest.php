<?php

use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\NomorSuratCounter;
use App\Models\PengajuanPkl;
use App\Services\LetterNumberService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('nomor surat berurutan per tahun ajaran dan jenis', function () {
    $service = app(LetterNumberService::class);

    $pertama = $service->allocate('2025/2026', 'surat_pengantar');
    $kedua = $service->allocate('2025/2026', 'surat_pengantar');
    $periodeLain = $service->allocate('2026/2027', 'surat_pengantar');
    $jenisLain = $service->allocate('2025/2026', 'surat_tugas_pembimbing');

    expect($pertama['nomor_urut'])->toBe(1)
        ->and($pertama['nomor_surat'])->toBe('421.5/0001/SMKN.1-CADISDIK WIL 3')
        ->and($kedua['nomor_urut'])->toBe(2)
        ->and($kedua['nomor_surat'])->toBe('421.5/0002/SMKN.1-CADISDIK WIL 3')
        ->and($periodeLain['nomor_urut'])->toBe(1)
        ->and($jenisLain['nomor_urut'])->toBe(1)
        ->and($jenisLain['nomor_surat'])->toBe('KPG.11.01/0001/SMKN.1-CADISDIK WIL 3');
});

test('rollback membatalkan pemakaian nomor', function () {
    $service = app(LetterNumberService::class);

    expect($service->allocate('2025/2026', 'surat_tugas_visitasi')['nomor_urut'])->toBe(1);

    try {
        DB::transaction(function () use ($service): void {
            $service->allocate('2025/2026', 'surat_tugas_visitasi');

            throw new RuntimeException('gagal setelah mengambil nomor');
        });
    } catch (RuntimeException) {
        // Diharapkan: transaksi dibatalkan.
    }

    expect($service->allocate('2025/2026', 'surat_tugas_visitasi')['nomor_urut'])->toBe(2)
        ->and(NomorSuratCounter::query()->first()?->last_number)->toBe(2);
});

test('database menolak nomor urut ganda per tahun ajaran', function () {
    $period = AcademicPeriod::factory()->create();

    $attributes = [
        'company_id' => Company::factory()->create()->id,
        'academic_period_id' => $period->id,
        'tahun_ajaran' => '2025/2026',
        'nomor_urut' => 1,
        'nomor_surat' => '421.5/0001/SMKN.1-CADISDIK WIL 3',
    ];

    PengajuanPkl::query()->create($attributes);

    expect(fn () => PengajuanPkl::query()->create([
        'company_id' => Company::factory()->create()->id,
        'academic_period_id' => $period->id,
        'tahun_ajaran' => '2025/2026',
        'nomor_urut' => 1,
        'nomor_surat' => '421.5/0001/SMKN.1-CADISDIK WIL 3 (duplikat)',
    ]))->toThrow(QueryException::class);
});
