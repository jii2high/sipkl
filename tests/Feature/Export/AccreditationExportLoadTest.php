<?php

use App\Jobs\AccreditationExportJob;
use App\Models\AcademicPeriod;
use App\Models\AccreditationExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Uji beban F13 (BR-F13-3/KP-F13-4): 1.752 siswa seperti skala nyata sekolah.
 * Dikeluarkan dari suite default (`phpunit.xml` exclude group `load`) dan
 * dijalankan manual: `vendor/bin/pest --group load`.
 */
test('ekspor 1.752 siswa selesai di bawah target', function () {
    Queue::fake();
    Storage::fake('local');

    $hubin = pklUser(['hubin']);
    $period = AcademicPeriod::factory()->create();

    Schema::disableForeignKeyConstraints();

    $batch = [];

    for ($i = 1; $i <= 1752; $i++) {
        $batch[] = [
            'pengajuan_siswa_id' => 900000 + $i,
            'form_submission_id' => 900000 + $i,
            'nama_siswa' => 'Siswa '.$i,
            'nis' => str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            'jurusan' => 'RPL',
            'nama_perusahaan' => 'PT Contoh '.$i,
            'academic_period_id' => $period->id,
            'nilai_total_per_kategori' => json_encode(['Soft Skill' => 85, 'Teknis' => 90]),
            'status_kelulusan' => 'Lulus',
            'nama_penguji' => 'Penguji '.$i,
            'tanggal_sidang' => '2026-06-01',
        ];

        if (count($batch) === 500) {
            DB::table('sidang_results_summary')->insert($batch);
            $batch = [];
        }
    }

    if ($batch !== []) {
        DB::table('sidang_results_summary')->insert($batch);
    }

    Schema::enableForeignKeyConstraints();

    $export = AccreditationExport::query()->create([
        'requested_by' => $hubin->getKey(),
        'status' => 'queued',
        'requested_at' => now(),
    ]);

    $start = hrtime(true);
    (new AccreditationExportJob($export->getKey()))->handle();
    $seconds = (hrtime(true) - $start) / 1e9;

    dump(sprintf('[uji beban F13] 1.752 baris diekspor dalam %.2f detik.', $seconds));

    $export->refresh();

    expect($export->status)->toBe('completed')
        ->and($export->row_count)->toBe(1752)
        ->and($seconds)->toBeLessThan((float) config('pkl.export.target_seconds'));
})->group('load');
