<?php

use App\Enums\PengajuanStatus;
use App\Filament\App\Pages\KanbanKelas;
use App\Filament\App\Pages\Monitoring;
use App\Models\AcademicPeriod;
use App\Models\Company;
use App\Models\DocumentSignature;
use App\Models\Kelas;
use App\Models\LaporanVersion;
use App\Models\PengajuanPkl;
use App\Models\PengajuanSiswa;
use App\Models\User;
use App\Services\MonitoringService;
use App\Support\JakartaTime;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;

/**
 * @return array{period: AcademicPeriod, company: Company, group: PengajuanPkl}
 */
function monitoringBase(int $nomorUrut = 1): array
{
    $period = AcademicPeriod::factory()->create();
    $company = Company::factory()->create();

    $group = PengajuanPkl::query()->create([
        'company_id' => $company->id,
        'academic_period_id' => $period->id,
        'tahun_ajaran' => '2025/2026',
        'nomor_urut' => $nomorUrut,
        'nomor_surat' => '421.5/'.str_pad((string) $nomorUrut, 4, '0', STR_PAD_LEFT).'/SMKN.1-CADISDIK WIL 3',
    ]);

    return compact('period', 'company', 'group');
}

function monitoringPengajuan(array $base, User $siswa, string $status = 'draft', array $extra = []): PengajuanSiswa
{
    return PengajuanSiswa::query()->create([
        'pengajuan_pkl_id' => $base['group']->getKey(),
        'company_id' => $base['company']->getKey(),
        'academic_period_id' => $base['period']->getKey(),
        'siswa_id' => $siswa->getKey(),
        'status' => $status,
        'snapshot_company_json' => ['nama' => $base['company']->nama],
        'tanggal_mulai_pkl' => '2026-01-05',
        'tanggal_selesai_pkl' => '2026-04-30',
        ...$extra,
    ]);
}

test('walas hanya melihat kanban kelasnya sendiri', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));

    $kelasA = Kelas::factory()->create();
    $kelasB = Kelas::factory()->create();

    $walas = pklUser(['walas']);
    $walas->roles()->where('role', 'walas')->update(['kelas_id' => $kelasA->id]);

    $base = monitoringBase();
    $siswaA = User::factory()->create(['kelas_id' => $kelasA->id, 'name' => 'Siswa Kelas A']);
    $siswaB = User::factory()->create(['kelas_id' => $kelasB->id, 'name' => 'Siswa Kelas B']);

    monitoringPengajuan($base, $siswaA, PengajuanStatus::MenungguVerifikasiKaprog->value);
    monitoringPengajuan($base, $siswaB, PengajuanStatus::MenungguVerifikasiKaprog->value);

    $this->actingAs($walas)->withSession(['active_role' => 'walas']);

    Livewire\Livewire::test(KanbanKelas::class)
        ->assertOk()
        ->assertSee('Siswa Kelas A')
        ->assertDontSee('Siswa Kelas B')
        ->assertDontSee('Tandatangani')
        ->assertDontSee('Setujui');
});

test('kanban menempatkan pengajuan sesuai posisi alur', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));

    $kelas = Kelas::factory()->create();
    $walas = pklUser(['walas']);
    $walas->roles()->where('role', 'walas')->update(['kelas_id' => $kelas->id]);

    $base = monitoringBase();
    $siswa = User::factory()->create(['kelas_id' => $kelas->id]);

    $pengajuan = monitoringPengajuan($base, $siswa, PengajuanStatus::SedangPkl->value);

    $version = LaporanVersion::query()->create([
        'pengajuan_siswa_id' => $pengajuan->getKey(),
        'version_number' => 1,
        'file_path' => 'laporan/v1.pdf',
        'is_legit' => false,
        'created_by' => $siswa->getKey(),
    ]);

    DocumentSignature::query()->create([
        'pengajuan_siswa_id' => $pengajuan->getKey(),
        'laporan_version_id' => $version->getKey(),
        'step_order' => 1,
        'signer_type' => 'guru_pembimbing',
        'status' => 'pending',
    ]);

    $this->actingAs($walas)->withSession(['active_role' => 'walas']);

    $board = Livewire\Livewire::test(KanbanKelas::class)->instance()->board();

    expect($board[3]['items'])->toHaveCount(0)
        ->and($board[4]['items'])->toHaveCount(1)
        ->and($board[5]['items'])->toHaveCount(0);

    $version->update(['is_legit' => true]);
    $board = Livewire\Livewire::test(KanbanKelas::class)->instance()->board();

    expect($board[4]['items'])->toHaveCount(0)
        ->and($board[5]['items'])->toHaveCount(1);
});

test('pengajuan ditolak dan ditarik tidak tampil di kanban', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));

    $kelas = Kelas::factory()->create();
    $walas = pklUser(['walas']);
    $walas->roles()->where('role', 'walas')->update(['kelas_id' => $kelas->id]);

    $base = monitoringBase();
    $siswa = User::factory()->create(['kelas_id' => $kelas->id, 'name' => 'Siswa Ditarik']);

    monitoringPengajuan($base, $siswa, PengajuanStatus::Withdrawn->value);

    $this->actingAs($walas)->withSession(['active_role' => 'walas']);

    $page = Livewire\Livewire::test(KanbanKelas::class)->instance();

    expect($page->hiddenCount())->toBe(1)
        ->and(collect($page->board())->sum(fn (array $kolom): int => $kolom['items']->count()))->toBe(0);
});

test('hanya walas yang dapat membuka kanban dan hanya kaprog yang dapat membuka dashboard', function () {
    session(['active_role' => 'walas']);
    expect(KanbanKelas::canAccess())->toBeTrue()
        ->and(Monitoring::canAccess())->toBeFalse();

    session(['active_role' => 'kaprog']);
    expect(KanbanKelas::canAccess())->toBeFalse()
        ->and(Monitoring::canAccess())->toBeTrue();
});

test('dashboard monitoring dapat dibuka Kaprog dan Hubin', function () {
    Filament::setCurrentPanel(Filament::getPanel('app'));

    $kaprog = pklUser(['kaprog']);
    $this->actingAs($kaprog)->withSession(['active_role' => 'kaprog']);

    $this->get('/app/monitoring')->assertOk()->assertSee(__('pkl.monitor.judul'));

    Filament::setCurrentPanel(Filament::getPanel('hubin'));

    $hubin = pklUser(['hubin']);
    $this->actingAs($hubin)->withSession(['active_role' => 'hubin']);

    $this->get('/hubin/monitoring')
        ->assertOk()
        ->assertSee(__('pkl.monitor.judul'))
        ->assertSee(__('pkl.monitor.gate'))
        ->assertSee(__('pkl.monitor.ttd'));
});

test('durasi tunggu step 5-TTD dihitung dalam hari kerja', function () {
    $base = monitoringBase();
    $siswa = User::factory()->create();

    $pengajuan = monitoringPengajuan($base, $siswa, PengajuanStatus::SedangPkl->value);

    $version = LaporanVersion::query()->create([
        'pengajuan_siswa_id' => $pengajuan->getKey(),
        'version_number' => 1,
        'file_path' => 'laporan/v1.pdf',
        'is_legit' => false,
        'created_by' => $siswa->getKey(),
    ]);

    $signature = DocumentSignature::query()->create([
        'pengajuan_siswa_id' => $pengajuan->getKey(),
        'laporan_version_id' => $version->getKey(),
        'step_order' => 1,
        'signer_type' => 'guru_pembimbing',
        'status' => 'pending',
    ]);

    // Timestamp DB diisi jam server, jadi waktu tunggu diatur eksplisit (Jumat).
    DocumentSignature::query()->whereKey($signature->getKey())->update([
        'created_at' => CarbonImmutable::parse('2026-11-20 09:00', JakartaTime::TZ)->utc(),
    ]);

    $this->travelTo(CarbonImmutable::parse('2026-11-23 09:00', JakartaTime::TZ));

    $steps = app(MonitoringService::class)->ttdSteps();

    expect($steps[0]['step'])->toBe(1)
        ->and($steps[0]['pending'])->toBe(1)
        ->and($steps[0]['rata'])->toBe(1)
        ->and($steps[0]['terlama'])->toBe(1)
        ->and($steps[4]['pending'])->toBe(0);
});

test('antrean gate menampilkan jumlah, durasi, dan peringatan kuota', function () {
    $base = monitoringBase();

    for ($i = 1; $i <= 5; $i++) {
        monitoringPengajuan($base, User::factory()->create(), PengajuanStatus::SedangPkl->value);
    }

    $antrean = monitoringPengajuan($base, User::factory()->create(), PengajuanStatus::MenungguVerifikasiKaprog->value);

    PengajuanSiswa::query()->whereKey($antrean->getKey())->update([
        'updated_at' => CarbonImmutable::parse('2026-11-20 09:00', JakartaTime::TZ)->utc(),
    ]);

    $this->travelTo(CarbonImmutable::parse('2026-11-23 09:00', JakartaTime::TZ));

    $gates = app(MonitoringService::class)->gates();

    expect($gates[0]['status'])->toBe(PengajuanStatus::MenungguVerifikasiKaprog->value)
        ->and($gates[0]['jumlah'])->toBe(1)
        ->and($gates[0]['rata'])->toBe(1)
        ->and($gates[0]['kuota'])->toHaveCount(1)
        ->and($gates[0]['kuota'][0]['tone'])->toBe('warning')
        ->and($gates[0]['kuota'][0]['used'])->toBe(5);
});
