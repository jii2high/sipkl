<?php

use App\Actions\Export\StartAccreditationExport;
use App\Filament\Hubin\Pages\EksporAkreditasi;
use App\Jobs\AccreditationExportJob;
use App\Jobs\PurgeExpiredExportsJob;
use App\Jobs\SendNotificationJob;
use App\Models\AcademicPeriod;
use App\Models\AccreditationExport;
use App\Notifications\Notification;
use App\Support\StatusChip;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

function exportSummary(AcademicPeriod $period, int $index, array $override = []): void
{
    Schema::disableForeignKeyConstraints();

    DB::table('sidang_results_summary')->insert([
        'pengajuan_siswa_id' => 900000 + $index,
        'form_submission_id' => 900000 + $index,
        'nama_siswa' => 'Siswa '.$index,
        'nis' => '0000'.$index,
        'jurusan' => 'RPL',
        'nama_perusahaan' => 'PT Contoh',
        'academic_period_id' => $period->id,
        'nilai_total_per_kategori' => json_encode(['Soft Skill' => 85, 'Teknis' => 90]),
        'status_kelulusan' => 'Lulus',
        'nama_penguji' => 'Penguji '.$index,
        'tanggal_sidang' => '2026-06-01',
        ...$override,
    ]);

    Schema::enableForeignKeyConstraints();
}

function exportRow(array $attributes = []): AccreditationExport
{
    return AccreditationExport::query()->create([
        'requested_by' => pklUser(['hubin'])->getKey(),
        'status' => 'queued',
        'requested_at' => now(),
        ...$attributes,
    ]);
}

test('hubin memulai ekspor dan job masuk antrean', function () {
    Queue::fake();

    $hubin = pklUser(['hubin']);
    $period = AcademicPeriod::factory()->create();

    $export = app(StartAccreditationExport::class)->handle($hubin, $period->getKey());

    expect($export->status)->toBe('queued')
        ->and($export->academic_period_id)->toBe($period->getKey());

    Queue::assertPushed(AccreditationExportJob::class);
});

test('peran selain hubin tidak dapat memulai ekspor', function () {
    Queue::fake();

    $kaprog = pklUser(['kaprog']);

    expect(fn () => app(StartAccreditationExport::class)->handle($kaprog))
        ->toThrow(AuthorizationException::class);

    expect(AccreditationExport::query()->count())->toBe(0);
});

test('job selesai menulis snapshot, checksum, dan mengantre notifikasi tautan', function () {
    Queue::fake();
    Storage::fake('local');

    $hubin = pklUser(['hubin']);
    $period = AcademicPeriod::factory()->create();

    exportSummary($period, 1);
    exportSummary($period, 2);

    $export = exportRow(['requested_by' => $hubin->getKey(), 'academic_period_id' => $period->getKey()]);

    (new AccreditationExportJob($export->getKey()))->handle();

    $export->refresh();

    expect($export->status)->toBe('completed')
        ->and($export->row_count)->toBe(2)
        ->and($export->snapshot_file_path)->not->toBeNull()
        ->and($export->snapshot_checksum)->toBe(hash('sha256', Storage::disk('local')->get($export->snapshot_file_path)))
        ->and($export->expires_at->greaterThan(now()->addDays(29)))->toBeTrue();

    Queue::assertPushed(SendNotificationJob::class, function (SendNotificationJob $job) use ($hubin): bool {
        return $job->notification instanceof Notification
            && $job->notifiable->is($hubin);
    });
});

test('baris tidak lengkap menggagalkan ekspor tanpa berkas yang dapat diunduh', function () {
    Queue::fake();
    Storage::fake('local');

    $hubin = pklUser(['hubin']);
    $period = AcademicPeriod::factory()->create();

    exportSummary($period, 1, ['nama_penguji' => '']);

    $export = exportRow(['requested_by' => $hubin->getKey(), 'academic_period_id' => $period->getKey()]);

    expect(fn () => (new AccreditationExportJob($export->getKey()))->handle())->toThrow(RuntimeException::class);

    $export->refresh();

    expect($export->status)->toBe('failed')
        ->and($export->failure_reason)->not->toBeNull()
        ->and($export->snapshot_file_path)->toBeNull()
        ->and(Storage::disk('local')->allFiles('exports'))->toBe([]);
});

test('unduhan ekspor hanya untuk hubin dan hanya attempt yang selesai', function () {
    Storage::fake('local');

    $hubin = pklUser(['hubin']);
    $kaprog = pklUser(['kaprog']);

    Storage::disk('local')->put('exports/ok.csv', 'isi');

    $ok = exportRow([
        'requested_by' => $hubin->getKey(),
        'status' => 'completed',
        'snapshot_file_path' => 'exports/ok.csv',
        'snapshot_checksum' => hash('sha256', 'isi'),
        'row_count' => 1,
        'completed_at' => now(),
        'expires_at' => now()->addDays(10),
    ]);

    $expired = exportRow([
        'requested_by' => $hubin->getKey(),
        'status' => 'completed',
        'snapshot_file_path' => 'exports/ok.csv',
        'snapshot_checksum' => hash('sha256', 'isi'),
        'row_count' => 1,
        'completed_at' => now()->subDays(40),
        'expires_at' => now()->subDay(),
    ]);

    $failed = exportRow(['requested_by' => $hubin->getKey(), 'status' => 'failed', 'failure_reason' => 'x']);

    $this->actingAs($hubin)->withSession(['active_role' => 'hubin']);
    $this->get(route('export.download', $ok))->assertOk();

    expect(Gate::allows('download', $expired))->toBeFalse()
        ->and(Gate::allows('download', $failed))->toBeFalse();

    $this->actingAs($kaprog)->withSession(['active_role' => 'kaprog']);
    $this->get(route('export.download', $ok))->assertForbidden();
});

test('purge menghapus berkas dan baris ekspor kedaluwarsa', function () {
    Storage::fake('local');

    Storage::disk('local')->put('exports/lama.csv', 'isi');
    Storage::disk('local')->put('exports/baru.csv', 'isi');

    $row = exportRow([
        'status' => 'completed',
        'snapshot_file_path' => 'exports/lama.csv',
        'snapshot_checksum' => hash('sha256', 'isi'),
        'row_count' => 1,
        'completed_at' => now()->subDays(40),
        'expires_at' => now()->subDay(),
    ]);

    $fresh = exportRow([
        'status' => 'completed',
        'snapshot_file_path' => 'exports/baru.csv',
        'snapshot_checksum' => hash('sha256', 'isi'),
        'row_count' => 1,
        'completed_at' => now(),
        'expires_at' => now()->addDays(10),
    ]);

    app(PurgeExpiredExportsJob::class)->handle();

    expect(AccreditationExport::query()->find($row->getKey()))->toBeNull()
        ->and(AccreditationExport::query()->find($fresh->getKey()))->not->toBeNull()
        ->and(Storage::disk('local')->exists('exports/lama.csv'))->toBeFalse()
        ->and(Storage::disk('local')->exists('exports/baru.csv'))->toBeTrue();
});

test('halaman ekspor akreditasi tampil untuk hubin', function () {
    Filament::setCurrentPanel(Filament::getPanel('hubin'));

    exportRow();

    $this->actingAs(pklUser(['hubin']))->withSession(['active_role' => 'hubin']);

    $this->get('/hubin/ekspor-akreditasi')
        ->assertOk()
        ->assertSee(__('pkl.export.judul'))
        ->assertSee(StatusChip::label('queued'));
});

test('hubin memicu ekspor dari halaman dan retry membuat attempt baru', function () {
    Queue::fake();
    Filament::setCurrentPanel(Filament::getPanel('hubin'));

    $hubin = pklUser(['hubin']);
    $period = AcademicPeriod::factory()->create();

    $this->actingAs($hubin)->withSession(['active_role' => 'hubin']);

    Livewire\Livewire::test(EksporAkreditasi::class)
        ->callAction('start', data: ['academic_period_id' => $period->getKey()])
        ->assertHasNoFormErrors();

    $first = AccreditationExport::query()->firstOrFail();

    expect($first->academic_period_id)->toBe($period->getKey());

    $first->update(['status' => 'failed', 'failure_reason' => 'contoh']);

    Livewire\Livewire::test(EksporAkreditasi::class)
        ->callAction(TestAction::make('retry')->table($first))
        ->assertOk();

    $retry = AccreditationExport::query()->whereKeyNot($first->getKey())->firstOrFail();

    expect($retry->retry_of_id)->toBe($first->getKey())
        ->and($retry->academic_period_id)->toBe($period->getKey());
});

test('hanya hubin yang dapat membuka halaman ekspor akreditasi', function () {
    expect(EksporAkreditasi::canAccess())->toBeFalse();

    session(['active_role' => 'hubin']);

    expect(EksporAkreditasi::canAccess())->toBeTrue();
});
